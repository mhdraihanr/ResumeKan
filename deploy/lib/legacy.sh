#!/usr/bin/env bash
# lib/legacy.sh — helper untuk jalur LEGACY (Railway / container tanpa systemd).
#
# Fungsi di sini hanya "deteksi + tolak dengan pesan jelas". Tujuannya supaya
# pemanggil lama (deploy.sh, update.sh, install-deps.sh) tidak diam-diam
# melakukan hal yang salah di VPS Debian/Ubuntu dengan systemd.
#
# VPS modern WAJIB memakai:
#   deploy/bootstrap.sh   (sekali, menyiapkan OS)
#   deploy/release.sh     (setiap update)

_DEBIAN_HOST_WITH_SYSTEMD() {
	[ -r /etc/os-release ] || return 1
	# shellcheck disable=SC1091
	. /etc/os-release
	case "${ID:-}" in
		debian|ubuntu|linuxmint) [ -d /run/systemd/system ] && return 0 ;;
	esac
	return 1
}

# refuse_on_systemd_host <nama-skrip> <skrip-pengganti>
# Panggil di awal script legacy. Kalau dijalankan di VPS systemd, cetak
# penjelasan dan keluar dengan kode 2 (bukan 1: beda dari error runtime biasa).
#
# Override: ALLOW_LEGACY_ON_SYSTEMD=1 supaya skrip boleh lanjut (untuk menguji
# skrip legacy secara sengaja).
refuse_on_systemd_host() {
	local name="$1" replacement="$2"
	if [ "${ALLOW_LEGACY_ON_SYSTEMD:-0}" = "1" ]; then
		printf 'PERINGATAN: %s dijalankan di VPS systemd dengan ALLOW_LEGACY_ON_SYSTEMD=1.\n' "$name" >&2
		return 0
	fi
	if _DEBIAN_HOST_WITH_SYSTEMD; then
		cat >&2 <<EOF

${name} adalah SKRIP LEGACY (dibuat untuk container Railway tanpa systemd).

Host ini Debian/Ubuntu dengan systemd. Pakai:
  sudo bash deploy/${replacement}

Kalau benar-benar ingin menjalankan versi legacy (mis. untuk audit), set:
  ALLOW_LEGACY_ON_SYSTEMD=1 bash deploy/${name}

EOF
		exit 2
	fi
	return 0
}
