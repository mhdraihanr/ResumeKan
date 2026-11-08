#!/usr/bin/env bash
# lib/composer.sh — pasang Composer 2 dengan verifikasi checksum SHA-384.
#
# Idempotent: kalau composer sudah ada versi 2.x, tidak memasang ulang.
# Di VPS, Composer dipanggil dengan user aplikasi (bukan root) supaya cache
# dan vendor/ tidak berubah owner.

_install_composer_bin() {
	have php || command -v php >/dev/null \
		|| fail "Instalasi Composer butuh PHP 8.3+ terlebih dahulu."

	local expected actual
	expected="$(curl -fsSL https://composer.github.io/installer.sig)"
	[ -n "$expected" ] || fail "Tidak bisa mengunduh checksum Composer."

	curl -fsSLo /tmp/composer-setup.php https://getcomposer.org/installer
	actual="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
	if [ "$expected" != "$actual" ]; then
		rm -f /tmp/composer-setup.php
		fail "Checksum Composer tidak cocok (host hilang/di-mirror rusak)."
	fi
	php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer >/dev/null
	rm -f /tmp/composer-setup.php
	chmod 0755 /usr/local/bin/composer
}

ensure_composer() {
	require_root
	if have composer; then
		local ver
		ver="$(composer --version 2>/dev/null | head -1)"
		log "Composer sudah ada: $ver"
		case "$ver" in
			Composer\ version\ 2.*) return 0 ;;
			*) warn "Composer versi < 2 terdeteksi; memasang ulang ke /usr/local/bin.";;
		esac
	fi
	apt_ensure ca-certificates curl unzip
	log "Pasang Composer 2"
	_install_composer_bin
	composer --version | head -1 >&2
}
