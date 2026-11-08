#!/usr/bin/env bash
# lib/dist.sh — unduh artifact Build Web (web-dist.tar.gz) dari GitHub Releases.
#
# Fungsi `fetch_dist` dipakai `lib/frontend.sh` (jalur VPS) dan skrip legacy
# (deploy.sh, update.sh) lewat include yang sama.
#
# Di mana artifact diterbitkan:
#   .github/workflows/build-web.yml → release tag `dist-latest`, asset
#   `web-dist.tar.gz`. URL selalu sama antar build, jadi tidak perlu update.

DIST_REPO="${DIST_REPO:-mhdraihanr/ResumeKan}"
DIST_TAG="${DIST_TAG:-dist-latest}"
DIST_ASSET="${DIST_ASSET:-web-dist.tar.gz}"
DIST_URL="${DIST_URL:-https://github.com/$DIST_REPO/releases/download/$DIST_TAG/$DIST_ASSET}"

# fetch_dist <dir_web> [dir_sementara]
# Kembalikan 0 kalau dist berhasil dipasang; selain itu bukan-0.
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

	local staging="$tmp_dir/dist-staging"
	rm -rf "$staging"
	mkdir -p "$staging" || { [ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"; return 1; }
	if ! tar -xzf "$archive" -C "$staging"; then
		[ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"
		return 1
	fi

	if [ ! -f "$staging/index.html" ]; then
		[ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"
		return 1
	fi

	rm -rf "$web_dir/dist"
	mv "$staging" "$web_dir/dist" || { [ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"; return 1; }

	[ "$keep_tmp" = "0" ] && rm -rf "$tmp_dir"
	return 0
}
