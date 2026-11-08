#!/usr/bin/env bash
# lib/tunnel.sh — pasang cloudflared sebagai unit systemd.
#
# Cloudflare Tunnel dipakai karena VPS di balik NAT dan inbound 80/443 tidak
# tersedia. Tunnel membuat koneksi KELUAR dari VPS ke jaringan Cloudflare;
# origin tetap lokal (Caddy di 127.0.0.1:8080).
#
# Token tunnel dibaca dari environment atau file. Fungsi ini TIDAK meminta
# token sebagai argumen di command-line, supaya tidak muncul di `ps` atau
# bash history.

CLOUDFLARED_REPO_KEY=https://pkg.cloudflare.com/cloudflare-main.gpg
CLOUDFLARED_REPO_LIST=/etc/apt/sources.list.d/cloudflared.list

_install_cloudflared_package() {
	step "Pasang cloudflared (repo resmi Cloudflare)"
	apt_ensure ca-certificates curl gnupg lsb-release

	if [ ! -f /usr/share/keyrings/cloudflare-main.gpg ]; then
		apt_repo_add_keyring "$CLOUDFLARED_REPO_KEY" /usr/share/keyrings/cloudflare-main.gpg \
			|| fail "Gagal mengunduh kunci cloudflared."
	fi

	if [ ! -f "$CLOUDFLARED_REPO_LIST" ]; then
		local codename
		codename="$(. /etc/os-release && printf '%s' "${VERSION_CODENAME:-$OS_CODENAME}")"
		printf 'deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared %s main\n' \
			"$codename" >"$CLOUDFLARED_REPO_LIST"
		chmod 0644 "$CLOUDFLARED_REPO_LIST"
	fi

	apt-get update -y >/dev/null || fail "apt-get update gagal setelah menambah repo cloudflared."
	apt_install cloudflared || {
		warn "Paket cloudflared dari repo gagal; coba unduh .deb langsung."
		_install_cloudflared_deb
	}
}

_install_cloudflared_deb() {
	local arch deb_url
	case "$(uname -m)" in
		x86_64|amd64) arch=amd64 ;;
		aarch64|arm64) arch=arm64 ;;
		*) fail "Arsitektur $(uname -m) tidak didukung untuk cloudflared." ;;
	esac
	deb_url="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-${arch}.deb"
	local tmp
	tmp="$(mktemp --suffix=.deb)"
	curl -fsSL "$deb_url" -o "$tmp" || fail "Gagal mengunduh $deb_url"
	apt_install "$tmp"
	rm -f "$tmp"
}

# tunnel_token_from_file <path>
# Baca token dari file. Mengizinkan format: isi file adalah token saja, atau
# baris dengan `TUNNEL_TOKEN=...`.
_tunnel_token_from_file() {
	local path="$1" raw
	[ -r "$path" ] || return 1
	raw="$(cat "$path")"
	case "$raw" in
		*TUNNEL_TOKEN=*) printf '%s' "$raw" | sed -n 's/^[[:space:]]*TUNNEL_TOKEN=[[:space:]]*//p' | head -1 ;;
		*) printf '%s' "$raw" ;;
	esac
}

# ensure_cloudflared_tunnel
# Variabel yang dibaca: CLOUDFLARE_TUNNEL_TOKEN (opsional), TUNNEL_TOKEN_FILE
# (default /etc/cloudflared/tunnel.env). Kalau token belum tersedia, service
# dipasang tapi TIDAK dijalankan-sukses: cetak instruksi tanpa mengeksekusi.
ensure_cloudflared_tunnel() {
	require_root
	ensure_cloudflared_bin

	local token="${CLOUDFLARE_TUNNEL_TOKEN:-}"
	if [ -z "$token" ] && [ -n "${TUNNEL_TOKEN_FILE:-}" ]; then
		token="$(_tunnel_token_from_file "$TUNNEL_TOKEN_FILE" || true)"
	fi
	if [ -z "$token" ] && [ -r /etc/cloudflared/tunnel.env ]; then
		token="$(_tunnel_token_from_file /etc/cloudflared/tunnel.env || true)"
	fi

	if [ -z "$token" ]; then
		warn "Token Tunnel tidak ditemukan; cloudflared belum dijalankan."
		cat >&2 <<'EOF'
Simpan token di /etc/cloudflared/tunnel.env dengan format:
  TUNNEL_TOKEN=<token-dari-dashboard-Cloudflare>
lalu jalankan:
  chmod 600 /etc/cloudflared/tunnel.env
  systemctl restart cloudflared
EOF
		return 1
	fi

	# Token masuk lewat argumen `service install` yang disediakan Cloudflare;
	# jangan echo token ke stdout, dan jangan simpan di bash history pemanggil
	# (token sudah dibaca dari file, bukan dari baris perintah ini).
	install -d -m 0755 /etc/cloudflared
	cloudflared service install "$token" >/dev/null 2>&1 \
		|| fail "cloudflared service install gagal."

	# Simpan juga ke file 0600 untuk pemasangan ulang (idempoten) tanpa perlu
	# meminta token lagi.
	if [ ! -f /etc/cloudflared/tunnel.env ]; then
		printf 'TUNNEL_TOKEN=%s\n' "$token" >/etc/cloudflared/tunnel.env
		chmod 0600 /etc/cloudflared/tunnel.env
	fi

	systemctl enable cloudflared >/dev/null 2>&1 || true
	systemctl restart cloudflared
	log "cloudflared aktif."
	systemctl --no-pager --lines=0 status cloudflared >&2 || true
}

# Versi tanpa token: pastikan biner ada tanpa mendaftarkan service.
ensure_cloudflared_bin() {
	if have cloudflared; then
		log "cloudflared sudah ada: $(cloudflared --version 2>/dev/null | head -1)"
		return 0
	fi
	_install_cloudflared_package
	have cloudflared || fail "cloudflared masih belum ada setelah instalasi."
}
