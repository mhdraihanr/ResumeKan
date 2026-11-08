#!/usr/bin/env bash
# lib/frontend.sh — sediakan web/dist dan api/public/print/index.html.
#
# Alur utama: unduh artifact Build Web dari release `dist-latest` (lib/dist.sh).
# Alur cadangan (kalau artifact tidak bisa diunduh): build lokal dengan pnpm.
# Build lokal memakan RAM; di VPS 1 GB bisa swap habis. Diutamakan CI.

# install_frontend <app_dir> [--allow-local-build]
install_frontend() {
	local app_dir="${1:-/srv/ResumeKan}"
	local allow_local="${2:-}" web="$app_dir/web" api="$app_dir/api"
	# Pemanggil (bootstrap.sh/release.sh) menetapkan APP_USER; default ke user service.
	local app_user="${APP_USER:-resumekan}"

	require_root
	[ -d "$web" ] || fail "Direktori web/ tidak ditemukan di $app_dir."
	id "$app_user" >/dev/null 2>&1 || fail "User '$app_user' tidak ada; jalankan bootstrap.sh dulu."

	# Root aplikasi harus bisa ditembus (x) oleh user caddy, tetapi tidak boleh
	# dibaca (r): caddy hanya butuh mencapai web/dist, bukan isi repo.
	chmod o+x "$app_dir" || true

	if (
		# shellcheck source=dist.sh
		. "$(dirname "${BASH_SOURCE[0]}")/dist.sh"
		fetch_dist "$web"
	); then
		log "Frontend dipasang dari artifact CI (dist-latest)."
	elif [ "$allow_local" = "--allow-local-build" ]; then
		warn "Unduhan artifact gagal; build lokal (lambat, bisa memakai swap)."
		_have_pnpm || fail "pnpm tidak ada, dan unduhan artifact gagal. Pasang Node 22 + pnpm atau aktifkan Build Web."
		(
			cd "$web" || exit 1
			pnpm install --frozen-lockfile
			pnpm type-check
			pnpm build:ci
		) || fail "Build frontend lokal gagal."
	else
		fail "Tidak bisa mendapatkan artifact frontend. Pastikan workflow Build Web sukses atau jalankan dengan --allow-local-build."
	fi

	[ -f "$web/dist/index.html" ] || fail "web/dist/index.html tidak ada setelah frontend dipasang."
	if [ ! -f "$web/dist/print.html" ]; then
		fail "web/dist/print.html tidak ada. Workflow Build Web harus menghasilkan print shell; jangan deploy tanpa file ini."
	fi

	install -d -o "$app_user" -g "$app_user" -m 0755 "$api/public/print"
	install -o "$app_user" -g "$app_user" -m 0644 "$web/dist/print.html" "$api/public/print/index.html"

	# Caddy (user sistem `caddy`) harus bisa membaca seluruh file statis.
	find "$web/dist" -type d -exec chmod 0755 {} +
	find "$web/dist" -type f -exec chmod 0644 {} +

	if ! runuser -u caddy -- test -r "$web/dist/index.html" 2>/dev/null; then
		fail "User caddy belum bisa membaca web/dist/index.html. Periksa permission $app_dir."
	fi

	log "Frontend siap dan dapat dibaca user caddy."
}

_have_pnpm() { command -v pnpm >/dev/null 2>&1; }
