#!/usr/bin/env bash
# lib-php.sh: naikkan paket PHP ke versi patch terbaru. Container tanpa systemd tidak
# menjalankan unattended-upgrades; rincian di deploy/README.md "Patch keamanan PHP".

php_versi_sekarang() {
	php -r 'echo PHP_VERSION;' 2>/dev/null || echo "tidak ada"
}

upgrade_php_packages() {
	local branch before after packages
	branch="${PHP_VERSION:-8.4}"

	if [ "${SKIP_PHP_UPGRADE:-0}" = "1" ]; then
		echo "[php] Upgrade paket dilewati (SKIP_PHP_UPGRADE=1)."
		return 0
	fi

	if ! command -v apt-get >/dev/null 2>&1; then
		echo "[php] apt-get tidak tersedia; upgrade paket dilewati."
		return 0
	fi

	# Hanya paket terpasang; pola php8.4-* tidak memaksa ekstensi baru ikut masuk.
	packages="$(
		dpkg-query -W -f='${db:Status-Abbrev}\t${binary:Package}\n' "php${branch}-*" 2>/dev/null \
			| awk -F'\t' '$1 ~ /^ii/ {print $2}' \
			| sort -u \
			|| true
	)"

	if [ -z "$packages" ]; then
		echo "[php] Tidak ada paket php${branch}-* terpasang; upgrade dilewati."
		return 0
	fi

	before="$(php_versi_sekarang)"
	echo "[php] Versi sekarang: $before"
	echo "[php] Paket yang diperiksa: $(echo "$packages" | tr '\n' ' ')"

	export DEBIAN_FRONTEND=noninteractive

	if ! apt-get update -y >/dev/null 2>&1; then
		echo "[php] PERINGATAN: apt-get update gagal. Versi PHP bisa jadi masih rentan."
		echo "[php]            Jalankan manual: apt-get update -y"
		return 0
	fi

	# --only-upgrade: hanya menaikkan paket yang ada. Flag confdef/confold supaya
	# php.ini lama dipertahankan dan dpkg tidak pernah menunggu jawaban.

	# shellcheck disable=SC2086
	if ! apt-get install -y --only-upgrade --no-install-recommends \
		-o Dpkg::Options::=--force-confdef \
		-o Dpkg::Options::=--force-confold \
		$packages >/dev/null 2>&1; then
		echo "[php] PERINGATAN: upgrade paket PHP gagal. Periksa manual:"
		echo "[php]            apt-get install -y --only-upgrade $(echo "$packages" | tr '\n' ' ')"
		return 0
	fi

	after="$(php_versi_sekarang)"
	echo "[php] Versi setelah upgrade: $after"

	if [ "$before" = "$after" ]; then
		echo "[php] Sudah versi terbaru di repo yang aktif."
	else
		echo "[php] Naik $before -> $after. Worker lama masih memakai biner lama"
		echo "[php] sampai backend di-restart di langkah berikutnya."
	fi

	return 0
}
