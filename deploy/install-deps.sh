#!/usr/bin/env bash
# install-deps.sh — pasang semua perangkat yang dibutuhkan deploy.sh.
#
# Dipanggil otomatis oleh deploy.sh kalau ada binary yang hilang, atau bisa
# dijalankan manual:  bash deploy/install-deps.sh
#
# Sifat skrip ini:
#   * Idempotent — tool yang sudah terpasang dilewati (dicek lewat `command -v`),
#     jadi aman dijalankan berkali-kali.
#   * Tanpa systemd — container Railway tidak punya systemd; Caddy dijalankan
#     oleh deploy.sh lewat `caddy start`.
#   * Tanpa secret — tidak ada kredensial di sini.
#
# Variabel lingkungan (opsional):
#   SKIP_CHROMIUM=1     lewati instalasi Chromium (PDF Browsershot tidak jalan).
#   PHP_VERSION=8.4     versi PHP yang dipasang (default 8.4).
#   NODE_MAJOR=22       versi mayor Node.js (default 22).
set -euo pipefail

PHP_VERSION="${PHP_VERSION:-8.4}"
NODE_MAJOR="${NODE_MAJOR:-22}"
SKIP_CHROMIUM="${SKIP_CHROMIUM:-0}"

log()  { printf '\n==> %s\n' "$*"; }
have() { command -v "$1" >/dev/null 2>&1; }
step() { printf '\n--- %s ---\n' "$*"; }

[ "$(id -u)" = "0" ] || { printf 'GAGAL: jalankan sebagai root.\n' >&2; exit 1; }

export DEBIAN_FRONTEND=noninteractive

# 1. Alat dasar ---------------------------------------------------------------
if have git && have curl && have tar && have unzip; then
	log "Alat dasar sudah ada (git, curl, tar, unzip)."
else
	log "1/6 Pasang alat dasar"
	apt-get update -y
	apt-get install -y --no-install-recommends \
		curl unzip git ca-certificates gnupg lsb-release tzdata screen procps
fi

# 2. PHP ----------------------------------------------------------------------
# Ubuntu 22.04 bawaan masih PHP 8.1, jadi pakai PPA ondrej/php untuk 8.4.
if have php && php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);'; then
	log "PHP >= 8.4 sudah ada ($(php -r 'echo PHP_VERSION;'))."
else
	log "2/6 Pasang PHP $PHP_VERSION"
	apt-get install -y --no-install-recommends software-properties-common
	add-apt-repository -y "ppa:ondrej/php"
	apt-get update -y
	apt-get install -y --no-install-recommends \
		"php${PHP_VERSION}-cli" \
		"php${PHP_VERSION}-pgsql" \
		"php${PHP_VERSION}-mbstring" \
		"php${PHP_VERSION}-xml" \
		"php${PHP_VERSION}-curl" \
		"php${PHP_VERSION}-zip" \
		"php${PHP_VERSION}-bcmath" \
		"php${PHP_VERSION}-intl" \
		"php${PHP_VERSION}-gd"
	# Pastikan `php` menunjuk ke versi yang baru dipasang.
	if [ -x "/usr/bin/php${PHP_VERSION}" ]; then
		update-alternatives --set php "/usr/bin/php${PHP_VERSION}" 2>/dev/null || true
	fi
fi

# 3. Composer -----------------------------------------------------------------
if have composer; then
	log "Composer sudah ada ($(composer --version 2>/dev/null | head -1))."
else
	log "3/6 Pasang Composer"
	EXPECTED="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	ACTUAL="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
	if [ "$EXPECTED" != "$ACTUAL" ]; then
		rm -f composer-setup.php
		printf 'GAGAL: checksum Composer tidak cocok.\n' >&2
		exit 1
	fi
	php composer-setup.php --install-dir=/usr/local/bin --filename=composer
	rm -f composer-setup.php
fi

# 4. Node.js + pnpm -----------------------------------------------------------
# Dibutuhkan Step 5 deploy.sh: `npm ci` mengisi api/node_modules untuk Puppeteer,
# dan pnpm jadi cadangan kalau unduhan dist dari CI gagal.
if have node && have npm && have pnpm; then
	log "Node.js, npm, pnpm sudah ada."
else
	log "4/6 Pasang Node.js $NODE_MAJOR + pnpm"
	if ! have node || ! have npm; then
		curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
		apt-get install -y --no-install-recommends nodejs
	fi
	# pnpm lewat corepack yang menempel di Node.
	if ! have pnpm; then
		corepack enable
		corepack prepare pnpm@latest --activate
	fi
fi

# 5. Chromium + font ----------------------------------------------------------
# Browsershot merender PDF lewat Chromium sistem. Di Ubuntu 22.04 paket
# `chromium` TIDAK tersedia (Ubuntu memindahkannya ke snap, dan snap tidak jalan
# di container). Jadi: coba `chromium`, dan kalau tidak ada kandidat, jatuh ke
# Google Chrome stable — yang memang path-nya dicari PdfService
# (/usr/bin/google-chrome-stable). Font dipasang agar PDF tidak jadi kotak-kotak.
if [ "$SKIP_CHROMIUM" = "1" ]; then
	log "5/6 Chromium dilewati (SKIP_CHROMIUM=1)."
elif have chromium || have chromium-browser || have google-chrome || have google-chrome-stable; then
	log "5/6 Chromium/Chrome sudah ada."
else
	log "5/6 Pasang Chromium/Chrome + font"
	apt-get install -y --no-install-recommends \
		fonts-liberation fonts-noto-core fonts-dejavu-core
	if apt-get install -y --no-install-recommends chromium 2>/dev/null; then
		echo "Chromium dipasang dari repo."
	else
		echo "Paket 'chromium' tidak tersedia; memasang Google Chrome stable."
		curl -fsSL https://dl.google.com/linux/linux_signing_key.pub \
			| gpg --dearmor -o /usr/share/keyrings/google-chrome.gpg
		echo "deb [arch=amd64 signed-by=/usr/share/keyrings/google-chrome.gpg] http://dl.google.com/linux/chrome/deb/ stable main" \
			> /etc/apt/sources.list.d/google-chrome.list
		apt-get update -y
		apt-get install -y --no-install-recommends google-chrome-stable
	fi
fi

# 6. Caddy --------------------------------------------------------------------
if have caddy; then
	log "Caddy sudah ada ($(caddy version 2>/dev/null | head -1))."
else
	log "6/6 Pasang Caddy"
	apt-get install -y --no-install-recommends debian-keyring debian-archive-keyring apt-transport-https
	curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
		| gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
	curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
		| tee /etc/apt/sources.list.d/caddy-stable.list >/dev/null
	apt-get update -y
	apt-get install -y --no-install-recommends caddy
fi

log "Selesai. Semua perangkat siap. Lanjutkan: bash /srv/ResumeKan/deploy/deploy.sh"
