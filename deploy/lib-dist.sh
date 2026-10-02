#!/usr/bin/env bash
# lib-dist.sh — mengunduh hasil build frontend dari GitHub Actions.
#
# Workflow ".github/workflows/build-web.yml" membangun web/ di runner GitHub lalu
# menerbitkan `web-dist.tar.gz` sebagai release asset pada tag tetap `dist-latest`.
# Container Railway free (0.5 CPU) tidak sanggup menjalankan `vite build`; ia cukup
# mengunduh arsip ini dan mengekstraknya ke web/dist.
#
# Format URL (release tag tetap, jadi selalu sama):
#   https://github.com/<owner>/<repo>/releases/download/dist-latest/web-dist.tar.gz
#
# Dipakai oleh deploy.sh dan update.sh. Fungsi ini mengembalikan 0 kalau berhasil
# memasang dist, dan bukan-0 kalau pemanggil harus membangun sendiri.

# DIST_REPO bisa ditimpa lewat lingkungan, mis. DIST_REPO=owner/repo.
DIST_REPO="${DIST_REPO:-mhdraihanr/ResumeKan}"
DIST_TAG="${DIST_TAG:-dist-latest}"
DIST_ASSET="${DIST_ASSET:-web-dist.tar.gz}"
DIST_URL="${DIST_URL:-https://github.com/$DIST_REPO/releases/download/$DIST_TAG/$DIST_ASSET}"

# fetch_dist <dir_web> [dir_sementara]
# Mengunduh arsip dist dan mengekstraknya ke <dir_web>/dist.
fetch_dist() {
	local web_dir="${1:-}"
	local tmp_dir="${2:-}"
	[ -n "$web_dir" ] || return 1

	local keep_tmp=1
	if [ -z "$tmp_dir" ]; then
		tmp_dir="$(mktemp -d)" || return 1
		keep_tmp=0
	fi
	mkdir -p "$tmp_dir" || return 1

	local archive="$tmp_dir/$DIST_ASSET"
	if ! curl -fsSL --retry 3 --retry-delay 2 --connect-timeout 10 \
		-o "$archive" "$DIST_URL"; then
		[ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"
		return 1
	fi

	# Ekstrak ke area bersih lalu pindahkan, supaya dist lama tidak bercampur.
	local staging="$tmp_dir/dist-staging"
	rm -rf "$staging"
	mkdir -p "$staging" || { [ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"; return 1; }
	if ! tar -xzf "$archive" -C "$staging"; then
		[ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"
		return 1
	fi

	# Wajib ada index.html; kalau tidak, arsipnya rusak.
	if [ ! -f "$staging/index.html" ]; then
		[ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"
		return 1
	fi

	rm -rf "$web_dir/dist"
	mv "$staging" "$web_dir/dist" || { [ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"; return 1; }

	[ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"
	return 0
}
