#!/usr/bin/env bash
# bootstrap.sh — siapkan VPS Debian/Ubuntu dari nol dalam satu perintah.
#
# Pakai:
#   sudo bash deploy/bootstrap.sh             # pasang semua yang belum ada
#   sudo bash deploy/bootstrap.sh --check     # hanya cek, tidak mengubah apa pun
#   sudo bash deploy/bootstrap.sh --skip-tunnel
#
# Yang dilakukan:
#   1. Deteksi OS (Debian 12 / Ubuntu 22.04 / 24.04 / Mint yang berbasis Ubuntu)
#   2. apt install paket dasar (git, curl, unzip, dll.)
#   3. PHP 8.4 (Sury untuk Debian, PPA ondrej/php untuk Ubuntu) + ekstensi wajib
#   4. Composer 2 (verifikasi checksum)
#   5. Node.js 22 + pnpm 10
#   6. Chromium / Google Chrome + font (Browsershot)
#   7. Caddy (repo resmi Cloudsmith)
#   8. cloudflared (repo resmi Cloudflare)
#   9. User `resumekan`, clone repo bila belum ada, `composer install`
#  10. web/dist (artifact CI) + api/public/print/index.html
#  11. Unit systemd `resumekan-api`, Caddyfile, dan (opsional) timer auto-update
#
# Skrip ini idempoten: aman dijalankan berulang. Kalau versi paket sudah benar,
# langkah yang tidak perlu dilewati. Tidak menyentuh database: migrasi TIDAK
# dijalankan di sini. Template .env hanya disalin kalau belum ada.
#
# Flag lingkungan:
#   APP_DIR=/srv/ResumeKan   direktori aplikasi (default)
#   APP_USER=resumekan       user service non-root (default)
#   APP_PORT=8000            port PHP CLI
#   ORIGIN_PORT=8080         port Caddy origin
#   WORKERS=2                PHP_CLI_SERVER_WORKERS
#   BRANCH=main              branch git
#   SKIP_CHROMIUM=1          lewati browser PDF
#   SKIP_PHP_UPGRADE=1       lewati upgrade paket PHP di akhir
#   ALLOW_LOCAL_FRONTEND=1   izinkan `pnpm build:ci` bila artifact tidak bisa diunduh
#   ENABLE_AUTO_UPDATE=1     aktifkan timer systemd auto-update
#   CLOUDFLARE_TUNNEL_TOKEN=<token>  atau TUNNEL_TOKEN_FILE=...
#
# Keluar dengan kode 0 hanya kalau kesehatan layanan lolos.
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/mhdraihanr/ResumeKan.git}"
APP_DIR="${APP_DIR:-/srv/ResumeKan}"
APP_USER="${APP_USER:-resumekan}"
APP_PORT="${APP_PORT:-8000}"
ORIGIN_PORT="${ORIGIN_PORT:-8080}"
WORKERS="${WORKERS:-2}"
BRANCH="${BRANCH:-main}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LIB_DIR="$SCRIPT_DIR/lib"

# shellcheck source=lib/common.sh
. "$LIB_DIR/common.sh"
# shellcheck source=lib/php.sh
. "$LIB_DIR/php.sh"
# shellcheck source=lib/composer.sh
. "$LIB_DIR/composer.sh"
# shellcheck source=lib/node.sh
. "$LIB_DIR/node.sh"
# shellcheck source=lib/pdf-browser.sh
. "$LIB_DIR/pdf-browser.sh"
# shellcheck source=lib/caddy.sh
. "$LIB_DIR/caddy.sh"
# shellcheck source=lib/tunnel.sh
. "$LIB_DIR/tunnel.sh"
# shellcheck source=lib/systemd.sh
. "$LIB_DIR/systemd.sh"
# shellcheck source=lib/frontend.sh
. "$LIB_DIR/frontend.sh"
# shellcheck source=lib/storage.sh
. "$LIB_DIR/storage.sh"

MODE="install"
SKIP_TUNNEL=0
for arg in "$@"; do
	case "$arg" in
		--check) MODE="check" ;;
		--skip-tunnel) SKIP_TUNNEL=1 ;;
		--help|-h)
			sed -n '2,40p' "$0"
			exit 0
			;;
		*) fail "Argumen tidak dikenal: $arg" ;;
	esac
done

require_root
detect_os || fail "OS tidak didukung. Hanya Debian 12/13 dan Ubuntu 22.04/24.04."
log "OS: $OS_ID $OS_VERSION ($OS_CODENAME)"

if [ "$MODE" = "check" ]; then
	# --check: laporkan yang sudah ada dan yang perlu dipasang, tanpa perubahan.
	missing=()
	for bin in git curl unzip php composer node npm pnpm caddy cloudflared; do
		have "$bin" || missing+=("$bin")
	done
	have php && php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);' 2>/dev/null \
		|| missing+=("php>=8.4")
	log "Yang sudah ada : $(for b in git curl unzip php composer node npm pnpm caddy cloudflared; do have "$b" && printf '%s ' "$b"; done)"
	log "Yang perlu ada  : ${missing[*]:-(tidak ada, semua siap)}"
	if [ -d "$APP_DIR/.git" ]; then
		log "Repository     : $APP_DIR ($(git_repo "$APP_DIR" rev-parse --abbrev-ref HEAD) @ $(git_repo "$APP_DIR" rev-parse --short HEAD))"
	else
		log "Repository     : belum ada di $APP_DIR"
	fi
	systemctl --no-pager status resumekan-api caddy cloudflared 2>&1 | grep -E '●|Active:' || true
	exit 0
fi

log "1/12 Paket dasar"
apt-get update -y >/dev/null || fail "apt-get update gagal."
apt_ensure ca-certificates curl gnupg git unzip zip acl apt-transport-https tzdata ca-certificates

log "2/12 PHP 8.4"
install_or_upgrade_php

log "3/12 Composer"
ensure_composer

log "4/12 Node.js + pnpm"
ensure_node

log "5/12 Browser PDF + font"
ensure_pdf_browser

log "6/12 Caddy"
ensure_caddy

log "7/12 cloudflared"
if [ "$SKIP_TUNNEL" = "1" ]; then
	warn "cloudflared dilewati (--skip-tunnel). Tunnel tidak akan dibuat."
else
	ensure_cloudflared_bin
fi

log "8/12 User $APP_USER dan repository"
if ! id "$APP_USER" >/dev/null 2>&1; then
	adduser --disabled-password --gecos '' "$APP_USER"
fi
install -d -o "$APP_USER" -g "$APP_USER" -m 0750 "$APP_DIR"

# Catatan: direktori kerja root milik resumekan dan group-nya resumekan,
# dengan mode 0750. Caddy cukup mendapat akses traverse (o+x) lewat
# lib/frontend.sh, dan tidak akan bisa membaca isi repo.

if [ ! -d "$APP_DIR/.git" ]; then
	# Clone sebagai user aplikasi supaya vendor/ dan .git tidak dimiliki root.
	runuser -u "$APP_USER" -- git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
else
	log "Repository sudah ada; tidak di-clone ulang."
fi

log "9/12 Composer install (sebagai $APP_USER)"
runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && composer install --no-dev --optimize-autoloader --no-interaction"
runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && composer check-platform-reqs --no-dev"

log "10/12 .env dan Node module (Puppeteer)"
if [ ! -f "$APP_DIR/api/.env" ]; then
	log "Menyalin template .env (mode 0600). Isi nilai GANTI_ sebelum migrasi."
	install -o "$APP_USER" -g "$APP_USER" -m 0600 \
		"$SCRIPT_DIR/.env.production.example" "$APP_DIR/api/.env"
	warn "Edit $APP_DIR/api/.env lalu jalankan 'php artisan migrate:status' sebelum migrate."
else
	log ".env sudah ada; tidak ditimpa."
fi
if [ -f "$APP_DIR/api/package-lock.json" ]; then
	runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && npm ci --no-audit --no-fund"
fi

log "11/12 Frontend dan print shell"
LOCAL_FRONTEND=""
[ "${ALLOW_LOCAL_FRONTEND:-0}" = "1" ] && LOCAL_FRONTEND="--allow-local-build"
install_frontend "$APP_DIR" "$LOCAL_FRONTEND"

log "12/12 Unit systemd + Caddyfile (+ timer opsional)"
# Pastikan kepemilikan/izin runtime benar SEBELUM unit dinyalakan. Semua langkah
# yang menjalankan sebagai root (composer, tar, cp) sudah selesai pada titik ini.
fix_runtime_ownership "$APP_DIR" "$APP_USER"

install_resumekan_api_unit "$APP_USER" "$APP_DIR" "$APP_PORT" "$WORKERS"
install_caddyfile "$APP_DIR" "$ORIGIN_PORT"

if ! wait_http "http://127.0.0.1:${APP_PORT}/up" 200 20; then
	fail "Backend tidak menjawab 200 di :${APP_PORT}/up. Lihat: journalctl -u resumekan-api -n 50 --no-pager"
fi
log "Backend :$APP_PORT/up → 200"

if ! curl -s -o /dev/null --max-time 5 -H 'Host: localhost' "http://127.0.0.1:${ORIGIN_PORT}/up"; then
	fail "Caddy tidak menjawab di 127.0.0.1:${ORIGIN_PORT}. Lihat: journalctl -u caddy -n 50 --no-pager"
fi
log "Caddy :$ORIGIN_PORT/up → 200"

install_resumekan_update_units "$APP_DIR"
if [ "${ENABLE_AUTO_UPDATE:-0}" = "1" ]; then
	systemctl enable --now resumekan-update.timer >/dev/null
	log "Auto-update aktif: resumekan-update.timer (15 menit)."
else
	log "Auto-update tersedia tapi belum aktif. Aktifkan: systemctl enable --now resumekan-update.timer"
fi

log "Upgrade paket PHP ke patch terbaru"
upgrade_php_packages || true
fix_runtime_ownership "$APP_DIR" "$APP_USER"
systemctl restart resumekan-api
wait_http "http://127.0.0.1:${APP_PORT}/up" 200 20 \
  || fail "Backend tidak pulih setelah upgrade PHP."

if [ "$SKIP_TUNNEL" = "0" ]; then
	log "Tunnel Cloudflare"
	ensure_cloudflared_tunnel || warn "Lanjutkan dengan menyimpan token ke /etc/cloudflared/tunnel.env."
fi

cat <<EOF

=== Selesai ===

Langkah manual yang masih perlu (sekali):

  1. Isi credential produksi di $APP_DIR/api/.env
       - APP_KEY: jalankan `php artisan key:generate --show` sebagai $APP_USER
       - Neon, Cloudinary, AI, Turnstile: nilai baru hasil rotasi

  2. Uji koneksi database tanpa mengubah schema:
       runuser -u $APP_USER -- bash -lc 'cd $APP_DIR/api && php artisan config:clear && php artisan migrate:status'
     Baru setelah itu:
       runuser -u $APP_USER -- bash -lc 'cd $APP_DIR/api && php artisan migrate --force && php artisan config:cache'

  3. Pastikan tunnel Cloudflare mengarah ke http://localhost:${ORIGIN_PORT}
     untuk hostname publik Anda.

Kesehatan layanan:
  systemctl is-active resumekan-api caddy cloudflared
  curl -s -o /dev/null -w '%{http_code}\\n' http://127.0.0.1:${APP_PORT}/up
  curl -s -o /dev/null -w '%{http_code}\\n' -H 'Host: localhost' http://127.0.0.1:${ORIGIN_PORT}/
EOF
