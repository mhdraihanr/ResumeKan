#!/usr/bin/env bash
# deploy.sh — bangun container ResumeKan dari nol (setelah redeploy Railway).
#
# Tahap 1 (opsional, di laptop):  bash deploy/deploy.sh --template
#   Membuat deploy/.env.production dari contoh, untuk diisi.
# Tahap 2 (di container):         bash /srv/ResumeKan/deploy/deploy.sh
#
# Skrip ini tidak memuat satu pun secret. APP_KEY dan DB_URL dibaca dari
# deploy/.env.production, yang tidak masuk git.
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/mhdraihanr/ResumeKan.git}"
APP_DIR="${APP_DIR:-/srv/ResumeKan}"
BRANCH="${BRANCH:-main}"
PHP_URL_PORT="${PHP_URL_PORT:-8000}"
WORKERS="${WORKERS:-4}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_TARGET="$SCRIPT_DIR/.env.production"
ENV_EXAMPLE="$SCRIPT_DIR/.env.production.example"

# Helper unduh dist dari CI (dipakai di langkah 6).
# shellcheck source=lib-dist.sh
. "$SCRIPT_DIR/lib-dist.sh"

log() { printf '\n==> %s\n' "$*"; }
fail() { printf '\nGAGAL: %s\n' "$*" >&2; exit 1; }

if [ "${1:-}" = "--template" ]; then
	[ -f "$ENV_TARGET" ] && fail "$ENV_TARGET sudah ada. Tidak ditimpa."
	cp "$ENV_EXAMPLE" "$ENV_TARGET"
	printf 'Dibuat %s.\nIsi nilai ISI_ lalu kirim ke container dengan scp.\n' "$ENV_TARGET"
	exit 0
fi

[ "$(id -u)" = "0" ] || fail "Jalankan sebagai root (di container)."

log "1/8 Periksa perangkat yang dibutuhkan"
# pnpm TIDAK diwajibkan: dist normalnya diunduh dari CI (butuh curl + tar).
# pnpm hanya dipakai kalau unduhan gagal, dan itu diperiksa di langkah 6.
for bin in git php composer caddy curl tar; do
	command -v "$bin" >/dev/null || fail "$bin tidak ditemukan. Selesaikan Langkah 4 dan 5 panduan dulu."
done
php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);' \
	|| fail "Butuh PHP >= 8.4, yang ada: $(php -v | head -1)"

log "2/8 Siapkan kode di $APP_DIR"
mkdir -p "$(dirname "$APP_DIR")"
if [ -d "$APP_DIR/.git" ]; then
	git -C "$APP_DIR" fetch origin "$BRANCH"
	git -C "$APP_DIR" checkout "$BRANCH"
	git -C "$APP_DIR" reset --hard "origin/$BRANCH"
else
	git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi

API="$APP_DIR/api"
WEB="$APP_DIR/web"

log "3/8 Pasang pengaturan dari deploy/.env.production"
[ -f "$ENV_TARGET" ] || fail "$ENV_TARGET tidak ada. Jalankan 'bash deploy/deploy.sh --template' di laptop, isi, lalu kirim ke container."
grep -q '^APP_KEY=base64:' "$ENV_TARGET" || fail "APP_KEY belum diisi di $ENV_TARGET."
grep -q '^DB_URL=postgres' "$ENV_TARGET" || fail "DB_URL belum diisi di $ENV_TARGET."
# Nilai ISI_ yang tertinggal biasanya baru ketahuan saat login gagal, jadi dicek di sini.
if grep -q '^[A-Z_]*=ISI_' "$ENV_TARGET"; then
	printf 'PERINGATAN: masih ada nilai ISI_ di %s:\n' "$ENV_TARGET"
	grep -n '^[A-Z_]*=ISI_' "$ENV_TARGET" || true
fi
install -m 600 "$ENV_TARGET" "$API/.env"

log "4/8 Pasang Caddyfile dan penjaga backend"
install -d -m 755 /etc/caddy /opt/resumekan /var/log/caddy
install -m 644 "$SCRIPT_DIR/Caddyfile" /etc/caddy/Caddyfile
install -m 755 "$SCRIPT_DIR/watchdog.sh" /opt/resumekan/watchdog.sh
touch /var/log/resumekan-api.log

log "5/8 Backend: composer, migrasi, izin"
cd "$API"
composer install --no-dev --optimize-autoloader --no-interaction
# Browsershot merender PDF lewat rantai PHP -> node -> api/node_modules/puppeteer
# -> Chromium. Tanpa `api/node_modules`, PDF gagal dengan ENOENT walau Chromium
# sistem terpasang. `npm ci` memakai package-lock.json; jatuh ke `npm install`
# kalau lock belum ada.
if [ -f package-lock.json ]; then
	npm ci --no-audit --no-fund
else
	npm install --no-audit --no-fund
fi
php artisan migrate --force
chown -R root:root storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

log "6/8 Frontend: pakai hasil build dari CI (fallback: build lokal)"
cd "$WEB"
# Hasil build idealnya datang dari GitHub Actions (workflow "Build Web"), yang
# berjalan di runner 2 core / 7 GB. Container ini hanya 0.5 CPU, dan `vite build`
# di sini terlihat hang di "transforming (xxxx)" selama puluhan menit.
# Unduhan gagal (repo private, CI belum jalan, jaringan) -> jatuh ke build lokal.
if fetch_dist "$WEB"; then
	echo "dist dipasang dari CI."
else
	echo "PERINGATAN: unduhan dist gagal; membangun lokal (mungkin lambat)."
	command -v pnpm >/dev/null \
		|| fail "pnpm tidak ada, dan unduhan dist dari CI gagal. Pasang Node 22 + pnpm, atau pastikan URL release bisa diakses."
	pnpm install --frozen-lockfile
	# `pnpm build` biasa menjalankan vue-tsc dan vite BERBARENGAN (run-p). Di 0.5 CPU
	# keduanya saling berebut CPU sampai Vite tampak berhenti. Build:ci hanya vite.
	pnpm type-check
	pnpm build:ci
fi
[ -f "$WEB/dist/index.html" ] || fail "dist tidak berisi index.html; build gagal."
install -d -m 755 "$API/public/print"
cp "$WEB/dist/print.html" "$API/public/print/index.html"
chmod -R a+rX "$WEB/dist"

log "7/8 Segarkan cache Laravel"
cd "$API"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

log "8/8 Nyalakan Caddy dan backend"
# Caddy: reload kalau sudah hidup, kalau belum jalankan di latar belakang.
# 'caddy start' menyalakan proses latar yang bisa di-reload lewat admin API.
if caddy reload --config /etc/caddy/Caddyfile 2>/dev/null; then
	echo "Caddy dimuat ulang."
else
	pkill -f 'caddy run' 2>/dev/null || true
	sleep 1
	caddy start --config /etc/caddy/Caddyfile
fi

# Backend: runit kalau ada, kalau tidak jalankan langsung dengan setsid.
if [ -d /etc/service ]; then
	install -d -m 755 /etc/service/resumekan-api
	install -m 755 "$SCRIPT_DIR/service-run.sh" /etc/service/resumekan-api/run
	echo "Backend diserahkan ke runit (/etc/service/resumekan-api)."
else
	pkill -f 'resumekan/watchdog.sh' 2>/dev/null || true
	sleep 1
	setsid nohup /opt/resumekan/watchdog.sh >/dev/null 2>&1 </dev/null &
	echo "Backend dijalankan dengan setsid."
fi

printf '\nTunggu backend siap...\n'
for _ in $(seq 1 15); do
	code="$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$PHP_URL_PORT/up" || true)"
	[ "$code" = "200" ] && break
	sleep 1
done

printf '\n'
printf 'backend  /up  : %s\n' "${code:-000}"
printf 'caddy   :8080 : %s\n' "$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/ || echo 000)"
printf 'proses        : %s caddy, %s php\n' \
	"$(pgrep -fc 'caddy run' || echo 0)" \
	"$(pgrep -fc 'php -S 127.0.0.1:8000' || echo 0)"

if [ "$code" != "200" ]; then
	printf '\nBackend belum menjawab 200. Periksa:\n  tail -30 /var/log/resumekan-api.log\n  tail -30 %s/storage/logs/laravel.log\n' "$API"
	exit 1
fi

printf '\nSelesai. Buka alamat Railway-mu di browser, lalu uji daftar dan login.\n'
