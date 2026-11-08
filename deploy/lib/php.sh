#!/usr/bin/env bash
# lib/php.sh — pasang PHP 8.4 secara idempoten.
#
# Debian: repository Sury (https://packages.sury.org/php). Bukan repo resmi
#         Debian; tercatat di Debian Wiki sebagai sumber resmi versi PHP tambahan.
# Ubuntu: PPA ondrej/php. Ini jalur yang dipakai deploy/install-deps.sh lama.
#
# Kalau PHP 8.4 sudah ada, fungsi ini hanya memutakhirkan paket (patch keamanan)
# lewat lib/php-upgrade.sh; tidak memasang ulang.
#
# Pintu masuk: install_or_upgrade_php
# Variabel: PHP_BRANCH (default 8.4), EXT_EXTRA (opsional, spasi-pisah)

PHP_BRANCH="${PHP_BRANCH:-8.4}"
PHP_EXTENSIONS="cli fpm pgsql mbstring xml curl zip bcmath intl gd"

_php_installed() {
	have php || return 1
	php -r 'exit(version_compare(PHP_VERSION, "'"$PHP_BRANCH"'.0", ">=") ? 0 : 1);' 2>/dev/null
}

_install_sury_debian() {
	step "Tambahkan repo Sury (Debian $OS_CODENAME)"
	apt_ensure ca-certificates curl gnupg apt-transport-https

	local workdir
	workdir="$(mktemp -d)"
	# shellcheck disable=SC2064
	trap "rm -rf '$workdir'" RETURN
	(
		cd "$workdir" || exit 1
		curl -fsSLO https://packages.sury.org/debsuryorg-archive-keyring.deb \
			|| exit 1
		apt_install ./debsuryorg-archive-keyring.deb || exit 1
	)
	trap - RETURN

	local sources=/etc/apt/sources.list.d/sury-php.sources
	if [ ! -f "$sources" ]; then
		printf 'Types: deb deb-src\nURIs: https://packages.sury.org/php\nSuites: %s\nComponents: main\nSigned-By: /usr/share/keyrings/debsuryorg-archive-keyring.gpg\n' \
			"$OS_CODENAME" >"$sources"
		chmod 0644 "$sources"
	fi
	apt-get update -y >/dev/null \
		|| fail "apt-get update gagal setelah menambah repo Sury."

	# Pastikan kandidat benar sebelum memasang; kesalahan source paling sering
	# muncul sebagai "kandidat tidak ada".
	if ! apt-cache policy "php${PHP_BRANCH}-cli" | grep -q "packages.sury.org"; then
		fail "php${PHP_BRANCH}-cli tidak ditemukan di repo Sury. Periksa $sources dan koneksi internet."
	fi
}

_install_ondrej_ubuntu() {
	step "Tambahkan PPA ondrej/php (Ubuntu $OS_VERSION)"
	apt_ensure software-properties-common ca-certificates
	export DEBIAN_FRONTEND=noninteractive
	add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 \
		|| fail "Gagal menambah PPA ondrej/php. Periksa koneksi atau status PPA."
	apt-get update -y >/dev/null \
		|| fail "apt-get update gagal setelah menambah PPA ondrej/php."

	if ! apt-cache policy "php${PHP_BRANCH}-cli" | grep -q "ondrej"; then
		fail "php${PHP_BRANCH}-cli tidak ditemukan di PPA ondrej/php."
	fi
}

_install_php_packages() {
	local pkgs=()
	local ext
	for ext in $PHP_EXTENSIONS $EXT_EXTRA; do
		[ -n "$ext" ] && pkgs+=("php${PHP_BRANCH}-${ext}")
	done
	step "Pasang PHP ${PHP_BRANCH} (${#pkgs[@]} paket)"
	apt_install "${pkgs[@]}"

	# Arahkan `php` ke versi yang baru dipasang bila alternatif ada.
	if have update-alternatives && [ -x "/usr/bin/php${PHP_BRANCH}" ]; then
		update-alternatives --set php "/usr/bin/php${PHP_BRANCH}" >/dev/null 2>&1 || true
	fi
}

_verify_php() {
	step "Verifikasi PHP"
	local php_bin
	php_bin="$(command -v php || true)"
	[ -n "$php_bin" ] || fail "php masih tidak ada setelah instalasi."

	local ver
	ver="$("$php_bin" -r 'echo PHP_VERSION;')"
	log "PHP CLI: $ver"
	"$php_bin" -r 'exit(version_compare(PHP_VERSION, "'"$PHP_BRANCH"'.0", ">=") ? 0 : 1);' \
		|| fail "PHP $ver < $PHP_BRANCH; cek versi yang diaktifkan."

	# Modul wajib: pdo_pgsql untuk Neon, mbstring/xml/curl/zip untuk Composer,
	# bcmath/intl/gd untuk aplikasi (validasi, locale, render gambar).
	local mods missing=()
	mods="$("$php_bin" -m)"
	local m
	for m in bcmath curl dom gd intl mbstring pdo_pgsql pgsql xml zip; do
		printf '%s\n' "$mods" | grep -qi "^${m}$" || missing+=("$m")
	done
	[ "${#missing[@]}" -eq 0 ] || fail "Modul PHP hilang: ${missing[*]}"
	log "Semua modul PHP wajib tersedia."
}

install_or_upgrade_php() {
	require_root
	detect_os || fail "OS tidak dikenal/ didukung. Hanya Debian/Ubuntu/Linux Mint."

	if _php_installed; then
		log "PHP >= $PHP_BRANCH sudah ada ($(php -r 'echo PHP_VERSION;')); hanya upgrade patch."
		# shellcheck source=php-upgrade.sh
		. "$(dirname "${BASH_SOURCE[0]}")/php-upgrade.sh"
		upgrade_php_packages
		_verify_php
		return 0
	fi

	apt-get update -y >/dev/null || fail "apt-get update gagal."
	apt_ensure ca-certificates curl gnupg unzip git

	if is_debian_based; then
		_install_sury_debian
	elif is_ubuntu_based; then
		_install_ondrej_ubuntu
	else
		fail "Instalasi PHP belum diimplementasikan untuk OS $OS_ID."
	fi

	_install_php_packages
	_verify_php
}
