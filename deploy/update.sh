#!/usr/bin/env bash
# update.sh — pasang kode terbaru dari GitHub ke container yang sedang hidup.
#
# Di laptop:  git push
# Di container: bash /srv/ResumeKan/deploy/update.sh
#
# Jalur ini tidak menyentuh .env, database, Caddyfile, dan penjaga backend.
# Kalau container di-redeploy (isi container hilang), pakai deploy.sh.
set -euo pipefail

APP_DIR="${APP_DIR:-/srv/ResumeKan}"
BRANCH="${BRANCH:-main}"
WORKERS="${WORKERS:-4}"
PHP_URL_PORT="${PHP_URL_PORT:-8000}"
DEPS_ONLY=0

[ "${1:-}" = "--deps-only" ] && DEPS_ONLY=1

log() { printf '\n==> %s\n' "$*"; }
fail() { printf '\nGAGAL: %s\n' "$*" >&2; exit 1; }

cd "$APP_DIR" || fail "$APP_DIR tidak ada. Jalankan deploy.sh dulu."

log "1/5 Tarik kode dari GitHub"
[ -d .git ] || fail "Bukan repo git. Jalankan deploy.sh dulu."
# Suntingan lokal akan hilang, jadi disimpan dulu supaya bisa dilihat lagi.
if ! git diff --quiet || ! git diff --cached --quiet; then
	PATCH="/root/update-lokal-$(date +%F-%H%M).patch"
	git diff HEAD >"$PATCH"
	printf 'PERINGATAN: ada perubahan lokal. Salinannya disimpan di %s\n' "$PATCH"
fi
git fetch origin "$BRANCH"
if [ "$DEPS_ONLY" = "1" ]; then
	git checkout "$BRANCH"
else
	git reset --hard "origin/$BRANCH"
fi

log "2/5 Backend: composer, migrasi, cache"
cd api
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
php artisan config:clear
php artisan route:clear
php artisan view:clear
# Migrasi dijalankan hanya kalau memang ada yang pending.
if php artisan migrate:status 2>/dev/null | grep -q 'Pending'; then
	php artisan migrate --force
else
	echo "Tidak ada migrasi baru."
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R root:root storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

log "3/5 Frontend: pnpm build"
cd ../web
pnpm install --frozen-lockfile
pnpm build
install -d -m 755 ../api/public/print
cp dist/print.html ../api/public/print/index.html
chmod -R a+rX dist

log "4/5 Muat ulang Caddy"
# Aset statis sudah dibaca ulang dari disk, jadi reload ini hanya perlu
# kalau Caddyfile ikut berubah. Kegagalan reload bukan alasan menghentikan update.
if ! caddy reload --config /etc/caddy/Caddyfile 2>/dev/null; then
	echo "Caddy tidak bisa dimuat ulang (mungkin tidak jalan lewat admin API). Dilewati."
fi

log "5/5 Muat ulang backend"
# php -S tidak memuat ulang kode sendiri, jadi harus dimatikan dan dinyalakan lagi.
# Watchdog akan menghidupkannya kembali dengan sendirinya, dan kalau watchdog
# tidak jalan, hasil install ikut memastikan penjaga ada di tempatnya.
install -m 755 "$APP_DIR/deploy/watchdog.sh" /opt/resumekan/watchdog.sh
pkill -f 'php -S 127.0.0.1:8000' || true
if ! pgrep -f 'resumekan/watchdog.sh' >/dev/null; then
	if [ -d /etc/service ]; then
		install -d -m 755 /etc/service/resumekan-api
		install -m 755 "$APP_DIR/deploy/service-run.sh" /etc/service/resumekan-api/run
	else
		setsid nohup /opt/resumekan/watchdog.sh >/dev/null 2>&1 </dev/null &
	fi
fi

printf '\nTunggu backend siap...\n'
code=000
for _ in $(seq 1 15); do
	code="$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$PHP_URL_PORT/up" || true)"
	[ "$code" = "200" ] && break
	sleep 1
done

printf '\nbackend : %s\n' "${code:-000}"
printf 'commit  : %s\n' "$(git -C "$APP_DIR" log -1 --pretty='%h %s')"

if [ "$code" != "200" ]; then
	printf '\nBackend belum menjawab 200. Periksa:\n  tail -30 /var/log/resumekan-api.log\n  tail -30 %s/api/storage/logs/laravel.log\n' "$APP_DIR"
	exit 1
fi

printf '\nSelesai. Muat ulang halaman di browser (Ctrl+F5) untuk membuang cache tampilan.\n'
