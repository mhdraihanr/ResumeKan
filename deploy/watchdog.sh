#!/usr/bin/env bash
# Penjaga backend: menghidupkan ulang php -S kalau mati. Pengganti systemd,
# yang tidak ada di container ini.
set -u

API_DIR=/srv/ResumeKan/api
PHP_BIN="$(command -v php || echo /usr/bin/php)"
LOG=/var/log/resumekan-api.log
# Tanpa ini, satu permintaan PDF memblokir seluruh situs karena php -S
# melayani satu permintaan sekaligus.
WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

if [ ! -d "$API_DIR" ]; then
	echo "[watchdog] $API_DIR tidak ada; keluar." >>"$LOG"
	exit 1
fi

cd "$API_DIR" || exit 1

# Batas unggah berkas. Default PHP distro cuma 2M, padahal fitur Impor CV
# menerima berkas sampai 5 MB. Flag -d WAJIB setelah "-S 127.0.0.1:8000"
# supaya pola pgrep/pkill '^php -S 127.0.0.1:8000' di deploy.sh/update.sh
# tetap cocok. post_max_size harus > upload_max_filesize.
UPLOAD_MAX_FILESIZE="${UPLOAD_MAX_FILESIZE:-6M}"
POST_MAX_SIZE="${POST_MAX_SIZE:-8M}"

# Jangan menumpuk instance: kalau sudah ada yang mendengarkan :8000, keluar saja.
if ps -eo args | grep -v grep | grep -q '^php -S 127\.0\.0\.1:8000'; then
	echo "[watchdog] sudah berjalan di :8000; keluar." >>"$LOG"
	exit 0
fi

while true; do
	echo "[watchdog] start $(date -Is)" >>"$LOG"
	PHP_CLI_SERVER_WORKERS="$WORKERS" "$PHP_BIN" -S 127.0.0.1:8000 -d upload_max_filesize="$UPLOAD_MAX_FILESIZE" -d post_max_size="$POST_MAX_SIZE" -t public public/index.php >>"$LOG" 2>&1
	echo "[watchdog] php -S berhenti, ulang 3 detik lagi $(date -Is)" >>"$LOG"
	sleep 3
done
