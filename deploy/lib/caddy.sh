#!/usr/bin/env bash
# lib/caddy.sh — pasang Caddy dari repo resmi Cloudsmith (Debian + Ubuntu).
#
# Satu-satunya syarat beda antar OS di sini adalah nama paket kunci GPG
# (debian-keyring / ubuntu-keyring), tapi keduanya menyediakan paket yang sama.
# Setelah terpasang, unit systemd `caddy` ikut terdaftar dan bisa di-enable.

ensure_caddy() {
	require_root
	if have caddy; then
		log "Caddy sudah ada: $(caddy version 2>/dev/null | head -1)"
		# Pastikan unit systemd terdaftar walau sudah ada; aman diulang.
		if systemctl list-unit-files caddy.service >/dev/null 2>&1; then
			systemctl enable caddy >/dev/null 2>&1 || true
		fi
		return 0
	fi

	step "Pasang Caddy dari repo resmi"
	if is_debian_based; then
		apt_ensure debian-keyring debian-archive-keyring apt-transport-https ca-certificates curl gnupg
	else
		apt_ensure ubuntu-keyring apt-transport-https ca-certificates curl gnupg
	fi

	local keyring=/usr/share/keyrings/caddy-stable-archive-keyring.gpg
	apt_repo_add_keyring https://dl.cloudsmith.io/public/caddy/stable/gpg.key "$keyring" \
		|| fail "Gagal mengunduh kunci GPG Caddy."

	local list=/etc/apt/sources.list.d/caddy-stable.list
	if [ ! -f "$list" ]; then
		curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' >"$list" \
			|| fail "Gagal mengunduh source Caddy untuk Debian/Ubuntu."
		chmod 0644 "$list"
	fi

	apt-get update -y >/dev/null || fail "apt-get update gagal setelah menambah repo Caddy."
	apt_install caddy
	systemctl enable caddy >/dev/null 2>&1 || true
	log "Caddy siap: $(caddy version | head -1)"
}
