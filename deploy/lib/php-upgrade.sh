#!/usr/bin/env bash
# lib/php-upgrade.sh — naikkan paket PHP ke versi patch terbaru.
#
# Dipakai:
#   * lib/php.sh → setelah memastikan PHP >= target sudah ada;
#   * bootstrap.sh → di akhir, supaya server baru langsung mendapat patch;
#   * release.sh   → sebelum restart backend.
#
# Sifat: tidak pernah menggagalkan deploy. Kalau apt gagal (mirror down,
# jaringan), skrip mencetak peringatan lalu lanjut, supaya situs tidak mati
# hanya karena masalah paket.
# Lewati dengan SKIP_PHP_UPGRADE=1.

php_versi_sekarang() {
	php -r 'echo PHP_VERSION;' 2>/dev/null || echo "tidak ada"
}

upgrade_php_packages() {
	local branch before after packages
	branch="${PHP_BRANCH:-8.4}"

	if [ "${SKIP_PHP_UPGRADE:-0}" = "1" ]; then
		echo "[php] Upgrade paket dilewati (SKIP_PHP_UPGRADE=1)." >&2
		return 0
	fi

	if ! command -v apt-get >/dev/null 2>&1; then
		echo "[php] apt-get tidak tersedia; upgrade paket dilewati." >&2
		return 0
	fi

	# Hanya paket yang sudah terpasang; pola php8.4-* tidak memaksa ekstensi baru.
	packages="$(
		dpkg-query -W -f='${db:Status-Abbrev}\t${binary:Package}\n' "php${branch}-*" 2>/dev/null \
			| awk -F'\t' '$1 ~ /^ii/ {print $2}' \
			| sort -u \
			|| true
	)"

	if [ -z "$packages" ]; then
		echo "[php] Tidak ada paket php${branch}-* terpasang; upgrade dilewati." >&2
		return 0
	fi

	before="$(php_versi_sekarang)"
	echo "[php] Versi sekarang: $before" >&2
	echo "[php] Paket yang diperiksa: $(echo "$packages" | tr '\n' ' ')" >&2

	export DEBIAN_FRONTEND=noninteractive

	if ! apt-get update -y >/dev/null 2>&1; then
		echo "[php] PERINGATAN: apt-get update gagal. Versi PHP bisa jadi masih rentan." >&2
		echo "[php]            Jalankan manual: apt-get update -y" >&2
		return 0
	fi

	# --only-upgrade: hanya menaikkan paket yang ada. Flag confdef/confold supaya
	# php.ini lama dipertahankan dan dpkg tidak pernah menunggu jawaban.
	# shellcheck disable=SC2086
	if ! apt-get install -y --only-upgrade --no-install-recommends \
		-o Dpkg::Options::=--force-confdef \
		-o Dpkg::Options::=--force-confold \
		$packages >/dev/null 2>&1; then
		echo "[php] PERINGATAN: upgrade paket PHP gagal. Periksa manual:" >&2
		echo "[php]            apt-get install -y --only-upgrade $(echo "$packages" | tr '\n' ' ')" >&2
		return 0
	fi

	after="$(php_versi_sekarang)"
	echo "[php] Versi setelah upgrade: $after" >&2

	if [ "$before" = "$after" ]; then
		echo "[php] Sudah versi terbaru di repo yang aktif." >&2
	else
		echo "[php] Naik $before -> $after. Worker lama masih memakai biner lama" >&2
		echo "[php] sampai backend di-restart di langkah berikutnya." >&2
	fi

	return 0
}
