#!/usr/bin/env bash
# release.sh — deploy versi terbaru ke VPS Debian/Ubuntu berbasis systemd.
#
# Pengganti deploy/update.sh untuk VPS. Perbedaan utama:
#   * TIDAK memakai `git reset --hard`. Suntingan lokal disimpan sebagai patch
#     dan merge memakai --ff-only: kalau terjadi konflik, deploy berhenti.
#   * Proses memakai systemd, bukan runit/setsid. Restart via `systemctl restart`.
#   * Health check 3 lapis: backend :8000/up, Caddy :8080/up, dan (opsional)
#     hostname publik.
#
# Pakai:
#   sudo bash deploy/release.sh                 # deploy penuh
#   sudo bash deploy/release.sh --if-changed    # no-op bila SHA lokal == origin
#   sudo bash deploy/release.sh --dry-run       # tampilkan yang akan dilakukan
#
# Diaktifkan juga oleh resumekan-update.timer (lihat deploy/systemd/resumekan-update.*).
# Timer memanggil dengan --if-changed --from-timer.
#
# Flag/lingkungan:
#   BRANCH=main             branch yang dilacak
#   SKIP_PHP_UPGRADE=1      lewati upgrade paket PHP
#   SKIP_MIGRATE=1          jangan jalankan migrasi pending
#   ALLOW_LOCAL_FRONTEND=1  izinkan build frontend lokal bila artifact gagal
#   PUBLIC_URL=https://...  hostname publik untuk uji tambahan (opsional)
set -euo pipefail

APP_DIR="${APP_DIR:-/srv/ResumeKan}"
APP_USER="${APP_USER:-resumekan}"
APP_PORT="${APP_PORT:-8000}"
ORIGIN_PORT="${ORIGIN_PORT:-8080}"
BRANCH="${BRANCH:-main}"
FROM_TIMER=0
IF_CHANGED=0
DRY_RUN=0

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LIB_DIR="$SCRIPT_DIR/lib"

# shellcheck source=lib/common.sh
. "$LIB_DIR/common.sh"
# shellcheck source=lib/php-upgrade.sh
. "$LIB_DIR/php-upgrade.sh"
# shellcheck source=lib/frontend.sh
. "$LIB_DIR/frontend.sh"
# shellcheck source=lib/storage.sh
. "$LIB_DIR/storage.sh"

for arg in "$@"; do
	case "$arg" in
		--if-changed) IF_CHANGED=1 ;;
		--from-timer) FROM_TIMER=1 ;;
		--dry-run) DRY_RUN=1 ;;
		--help|-h) sed -n '2,30p' "$0"; exit 0 ;;
		*) fail "Argumen tidak dikenal: $arg" ;;
	esac
done

require_root
[ -d "$APP_DIR/.git" ] || fail "$APP_DIR bukan repo git. Jalankan bootstrap.sh dulu."

cd "$APP_DIR"

# Repo milik $APP_USER, bukan root; git_repo menjalankan git sebagai pemilik
# supaya tidak kena "detected dubious ownership".
log "1/7 Cek versi dari origin/$BRANCH"
LOCAL_SHA="$(git_repo "$APP_DIR" rev-parse HEAD)"
if ! git_repo "$APP_DIR" fetch --quiet origin "$BRANCH"; then
	if [ "$FROM_TIMER" = "1" ]; then
		# Timer tidak boleh gagal keras karena jaringan sebentar putus.
		warn "git fetch gagal (dipanggil timer); coba lagi pada jadwal berikutnya."
		exit 0
	fi
	fail "git fetch gagal. Periksa koneksi internet dan remote origin."
fi
REMOTE_SHA="$(git_repo "$APP_DIR" rev-parse "origin/$BRANCH")"

if [ "$IF_CHANGED" = "1" ] && [ "$LOCAL_SHA" = "$REMOTE_SHA" ]; then
	log "Tidak ada perubahan. HEAD sudah di $LOCAL_SHA; keluar."
	exit 0
fi
log "Lokal: ${LOCAL_SHA:0:7} → Remote: ${REMOTE_SHA:0:7}"

if [ "$DRY_RUN" = "1" ]; then
	log "Mode dry-run: akan melakukan git merge --ff-only, composer install, artifact, migrate pending, restart."
	git_repo "$APP_DIR" --no-pager log --oneline "$LOCAL_SHA..$REMOTE_SHA"
	exit 0
fi

# Suntingan lokal di lingkungan produksi berbahaya: kalau ada perubahan pada
# file yang juga berubah di origin, merge akan gagal. Simpan patch agar bisa
# dilihat kembali -- tapi jangan auto-stash dan lanjut, karena hasil merge bisa
# tidak terduga di server produksi.
if ! git_repo "$APP_DIR" diff --quiet || ! git_repo "$APP_DIR" diff --cached --quiet; then
	PATCH="/root/release-lokal-$(date +%F-%H%M).patch"
	git_repo "$APP_DIR" diff HEAD >"$PATCH"
	chmod 0600 "$PATCH"
	warn "Ada perubahan lokal; patch disimpan di $PATCH"
	warn "Deploy akan memakai git merge --ff-only. Kalau gagal, selesaikan manual dulu."
fi

log "2/7 Git merge --ff-only"
if ! git_repo "$APP_DIR" merge --ff-only "origin/$BRANCH"; then
	fail "Fast-forward gagal. Ada perubahan lokal/history divergen. Patch disimpan di /root/."
fi

NEW_SHA="$(git_repo "$APP_DIR" rev-parse --short HEAD)"
NEW_SUBJECT="$(git_repo "$APP_DIR" log -1 --pretty='%s')"
log "Sekarang di $NEW_SHA — $NEW_SUBJECT"

log "3/7 Composer & dependencies API"
runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && composer install --no-dev --optimize-autoloader --no-interaction"
if [ -f "$APP_DIR/api/package-lock.json" ]; then
	runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && npm ci --no-audit --no-fund"
fi

log "4/7 Frontend"
LOCAL_FRONTEND=""
[ "${ALLOW_LOCAL_FRONTEND:-0}" = "1" ] && LOCAL_FRONTEND="--allow-local-build"
install_frontend "$APP_DIR" "$LOCAL_FRONTEND"

# Jalankan SETELAH semua langkah yang menulis sebagai root (frontend, print
# shell) supaya kepemilikan yang benar tidak tertimpa.
fix_runtime_ownership "$APP_DIR" "$APP_USER"

log "5/7 Migrasi (hanya yang pending)"
if [ "${SKIP_MIGRATE:-0}" = "1" ]; then
	warn "SKIP_MIGRATE=1; migrasi dilewati."
else
	# Cek pending lewat migrate:status, bukan langsung migrate.
	if runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && php artisan migrate:status" 2>/dev/null \
		| grep -q 'Pending'; then
		runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && php artisan migrate --force"
	else
		log "Tidak ada migrasi pending."
	fi
fi
runuser -u "$APP_USER" -- bash -lc "cd '$APP_DIR/api' && php artisan config:cache && php artisan route:cache && php artisan view:cache"

log "6/7 Upgrade paket PHP (patch keamanan) + restart"
upgrade_php_packages || true
systemctl restart resumekan-api

log "7/7 Health check"
if ! wait_http "http://127.0.0.1:${APP_PORT}/up" 200 30; then
	fail "Backend :${APP_PORT}/up bukan 200 setelah restart. Lihat journalctl -u resumekan-api -n 50 --no-pager"
fi
if ! wait_http "http://127.0.0.1:${ORIGIN_PORT}/up" 200 10; then
	fail "Caddy :${ORIGIN_PORT}/up bukan 200. Lihat journalctl -u caddy -n 50 --no-pager"
fi

# Halaman depan WAJIB dicek terpisah. /up hanya membuktikan backend hidup; ia
# lewat reverse_proxy dan tidak menyentuh filesystem, sehingga 403 akibat
# permission web/dist lolos tanpa terdeteksi. Tanpa cek ini, deploy dilaporkan
# sukses padahal situs menampilkan "403 Forbidden".
code_root="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "http://127.0.0.1:${ORIGIN_PORT}/" || true)"
if [ "$code_root" != "200" ]; then
	fail "Halaman depan :${ORIGIN_PORT}/ → $code_root (bukan 200). Kalau 403, user caddy tidak bisa membaca web/dist. Periksa: namei -l ${APP_DIR}/web/dist/index.html dan journalctl -u caddy -n 50 --no-pager"
fi
# print shell dipakai CvController::resolvePrintHtml; 200 di sini berarti file
# benar-benar tersaji, bukan hanya ada di disk.
code_print="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "http://127.0.0.1:${ORIGIN_PORT}/print/" || true)"
[ "$code_print" = "200" ] || warn "print shell :${ORIGIN_PORT}/print/ → $code_print (PDF mungkin gagal)"

if [ -n "${PUBLIC_URL:-}" ]; then
	code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 -L "${PUBLIC_URL%/}/up" || true)"
	[ "$code" = "200" ] || warn "PUBLIC_URL ${PUBLIC_URL%/}/up → $code (DNS/cloudflared mungkin belum siap)"
	code_pub="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 -L "${PUBLIC_URL%/}/" || true)"
	[ "$code_pub" = "200" ] || warn "PUBLIC_URL ${PUBLIC_URL%/}/ → $code_pub (bukan 200; cek Cloudflare Access/WAF atau izin web/dist)"
fi

cat <<EOF

=== Deploy selesai ===

  commit   : $NEW_SHA — $NEW_SUBJECT
  backend  : :${APP_PORT}/up → 200
  caddy    : :${ORIGIN_PORT}/up → 200
  waktu    : $(date -Is)

Muat ulang browser (Ctrl+F5) untuk membuang cache.
EOF
