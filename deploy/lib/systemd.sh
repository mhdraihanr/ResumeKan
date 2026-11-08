#!/usr/bin/env bash
# lib/systemd.sh — tulis unit systemd untuk ResumeKan dari template.
#
# Semua unit di sini dirancang untuk Debian 12 + Ubuntu 22.04/24.04 dengan
# systemd (tidak ada lagi runit/setsid seperti di deploy.sh lama).
#
# Template memakai placeholder {{NAMA}}, diganti dengan `sed` saat instalasi.
# Pendekatan ini dipilih supaya logika template tetap sederhana dan bisa
# diperiksa oleh shellcheck tanpa perlu engine templating tambahan.

SYSTEMD_TEMPLATE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../systemd" && pwd)"

# Lib ini sering di-source langsung dari shell root (tanpa lewat bootstrap.sh
# atau release.sh), mis. untuk memperbaiki unit saja. Pastikan fungsi dasar dari
# common.sh (require_root, log, fail, write_if_changed) sudah tersedia; kalau
# tidak, unit akan gagal ditulis dengan pesan yang membingungkan.
if ! declare -F require_root >/dev/null 2>&1; then
	# shellcheck source=./common.sh
	# shellcheck disable=SC1091
	. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/common.sh"
fi

# _render_template <template> <tujuan> [key=value...]
_render_template() {
	local src="$1" dst="$2"
	shift 2
	[ -r "$src" ] || fail "Template unit tidak ditemukan: $src"

	local tmp
	tmp="$(mktemp)"

	# Salin dulu, buang CRLF (berkas bisa saja tersimpan dengan akhiran baris
	# Windows), lalu ganti setiap placeholder.
	#
	# CRLF di unit systemd merusak baris lanjutan ExecStart: baris berakhir dengan
	# "\\\r" sehingga backslash-nya tidak dianggap escape dan sisa flag -d
	# dijalankan sebagai executable → unit gagal start. Caddyfile pun lebih buruk
	# lagi: Caddy bisa menganggap baris berikutnya sebagai argumen direktif atau
	# menolak file ber-CRLF.
	tr -d '\r' <"$src" >"$tmp"
	local kv key value
	for kv in "$@"; do
		key="${kv%%=*}"
		value="${kv#*=}"
		# Escape karakter khusus sed di value.
		value="$(printf '%s' "$value" | sed 's/[&/\]/\\&/g')"
		sed -i "s|{{${key}}}|${value}|g" "$tmp"
	done

	# Cek placeholder tidak sengaja tertinggal.
	if grep -q '{{[A-Z_]*}}' "$tmp"; then
		grep -o '{{[A-Z_]*}}' "$tmp" | sort -u | sed 's/^/  placeholder tertinggal: /' >&2
		rm -f "$tmp"
		fail "Template $src masih berisi placeholder yang tidak terganti."
	fi

	if write_if_changed "$dst" <"$tmp"; then
		rm -f "$tmp"
		return 0
	fi
	rm -f "$tmp"
	return 1
}

# install_resumekan_api_unit <app_user> <app_dir> <port> [workers]
install_resumekan_api_unit() {
	local user="${1:-resumekan}" dir="${2:-/srv/ResumeKan}" port="${3:-8000}" workers="${4:-2}"
	require_root
	local changed=0

	if _render_template \
		"$SYSTEMD_TEMPLATE_DIR/resumekan-api.service.tpl" \
		/etc/systemd/system/resumekan-api.service \
		"USER=$user" "APP_DIR=$dir" "PORT=$port" "WORKERS=$workers"; then
		changed=1
	fi
	[ "$changed" = "1" ] && systemctl daemon-reload

	systemctl enable resumekan-api.service >/dev/null 2>&1 || true
	systemctl restart resumekan-api.service
	log "Unit resumekan-api aktif (port $port, workers=$workers, user=$user)."
}

# install_resumekan_caddy_unit <app_dir> <origin_port>
# Menulis Caddyfile ke /etc/caddy saja (unit Caddy sudah dari paket).
install_caddyfile() {
	local app_dir="${1:-/srv/ResumeKan}" port="${2:-8080}"
	require_root

	local src="$SYSTEMD_TEMPLATE_DIR/Caddyfile.tpl"
	[ -r "$src" ] || fail "Template Caddyfile tidak ditemukan: $src"

	local tmp
	tmp="$(mktemp)"
	# tr -d '\r' dengan alasan sama seperti di _render_template.
	tr -d '\r' <"$src" \
		| sed -e "s|{{APP_DIR}}|${app_dir}|g" \
			-e "s|{{PORT}}|${port}|g" >"$tmp"

	# Cadangkan Caddyfile lama sekali, jangan menimpa cadangan sebelumnya.
	if [ -f /etc/caddy/Caddyfile ] && ! cmp -s "$tmp" /etc/caddy/Caddyfile; then
		local bak="/etc/caddy/Caddyfile.$(date +%Y%m%d-%H%M%S).bak"
		install -m 0644 /etc/caddy/Caddyfile "$bak"
		log "Caddyfile lama dicadangkan ke $bak"
	fi

	install -Dm 0644 "$tmp" /etc/caddy/Caddyfile
	rm -f "$tmp"

	# Validasi sebelum reload; Caddyfile rusak mematikan situs.
	caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile >/dev/null \
		|| fail "Caddyfile tidak valid; konfigurasi lama tidak diganti. Periksa: caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile"

	systemctl enable caddy >/dev/null 2>&1 || true
	systemctl restart caddy \
		|| fail "systemctl restart caddy gagal. Periksa: journalctl -u caddy -n 50 --no-pager"
	log "Caddy melayani origin http://127.0.0.1:${port}."
}

# install_resumekan_update_timer <app_dir>
# Timer systemd untuk release.sh (pull-based auto-update).
# Fungsi ini TIDAK mengaktifkan timer secara otomatis; pemanggil memutuskan.
install_resumekan_update_units() {
	local dir="${1:-/srv/ResumeKan}"
	require_root
	local changed=0

	_render_template \
		"$SYSTEMD_TEMPLATE_DIR/resumekan-update.service.tpl" \
		/etc/systemd/system/resumekan-update.service \
		"APP_DIR=$dir" && changed=1
	_render_template \
		"$SYSTEMD_TEMPLATE_DIR/resumekan-update.timer.tpl" \
		/etc/systemd/system/resumekan-update.timer && changed=1

	[ "$changed" = "1" ] && systemctl daemon-reload
	log "Timer update dipasang (belum aktif). Aktifkan dengan: systemctl enable --now resumekan-update.timer"
}
