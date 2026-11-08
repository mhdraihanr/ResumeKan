#!/usr/bin/env bash
# lib/storage.sh — pastikan kepemilikan dan izin direktori Laravel benar.
#
# Dipanggil oleh bootstrap.sh dan release.sh sebagai langkah TERAKHIR sebelum
# restart. Perintah yang "berbahaya" (Composer install, git merge, tar ekstrak,
# cp print shell) bisa membuat sebagian file milik root; fungsi ini
# mengembalikannya ke user aplikasi. Karena dijalankan di akhir, tidak ada
# langkah sesudahnya yang bisa merusak izin lagi.
#
# Aturan:
#   * $APP_DIR                : milik user aplikasi (0750) — Caddy cukup bisa
#                               menembus lewat chmod o+x, bukan baca isinya.
#   * $APP_DIR/api/storage    : 0775 user aplikasi (log, session, cache)
#   * $APP_DIR/api/bootstrap/cache : 0775 user aplikasi
#   * $APP_DIR/api/.env       : 0600 user aplikasi
#   * $APP_DIR/web/dist       : 0755 dir / 0644 file (harus dibaca Caddy)
#
# Tidak men-chown seluruh repo (vendor/ dan node_modules tidak perlu dan
# chown rekursif bisa lambat di VPS kecil). Hanya direktori yang ditulis
# runtime dan file yang baru dipasang.

fix_runtime_ownership() {
	local dir="${1:-/srv/ResumeKan}" user="${2:-resumekan}"

	require_root
	id "$user" >/dev/null 2>&1 || fail "User '$user' tidak ada."

	# 1) Root aplikasi bisa ditembus Caddy (o+x) tapi tidak bisa dibaca (o+r).
	chown "$user:$user" "$dir"
	chmod 0750 "$dir"

	# 2) Direktori yang ditulis Laravel saat runtime.
	local d
	for d in "$dir/api/storage" "$dir/api/bootstrap/cache"; do
		[ -d "$d" ] || continue
		chown -R "$user:$user" "$d"
		find "$d" -type d -exec chmod 0775 {} +
		find "$d" -type f -exec chmod 0664 {} +
	done

	# 3) .env harus hanya bisa dibaca user aplikasi (secret).
	if [ -f "$dir/api/.env" ]; then
		chown "$user:$user" "$dir/api/.env"
		chmod 0600 "$dir/api/.env"
	fi

	# 4) File statis untuk Caddy: user caddy hanya butuh baca.
	if [ -d "$dir/web/dist" ]; then
		find "$dir/web/dist" -type d -exec chmod 0755 {} +
		find "$dir/web/dist" -type f -exec chmod 0644 {} +
	fi

	# 5) Print shell dipasang dari web/dist; pastikan tetap terbaca Caddy.
	if [ -d "$dir/api/public/print" ]; then
		chown -R "$user:$user" "$dir/api/public/print"
		find "$dir/api/public/print" -type f -exec chmod 0644 {} +
	fi

	log "Kepemilikan & izin runtime diperbaiki (user=$user)."
}
