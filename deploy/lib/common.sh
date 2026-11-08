#!/usr/bin/env bash
# lib/common.sh — helper bersama untuk bootstrap.sh dan release.sh.
#
# Tujuan: satu kumpulan fungsi untuk semua OS. Skrip lain cukup `. lib/common.sh`
# lalu pakai `log`, `fail`, `require_root`, `detect_os`.
#
# Tidak memuat secret. Tidak memodifikasi apa pun saat di-source.

# Set -euo pipefail HARUS di-set oleh pemanggil (bootstrap.sh / release.sh),
# bukan di sini, karena file ini hanya berisi definisi fungsi.

log()  { printf '\n==> %s\n' "$*" >&2; }
step() { printf '\n--- %s ---\n' "$*" >&2; }
warn() { printf 'PERINGATAN: %s\n' "$*" >&2; }
fail() { printf '\nGAGAL: %s\n' "$*" >&2; exit 1; }

have() { command -v "$1" >/dev/null 2>&1; }

require_root() {
	[ "$(id -u)" = "0" ] || fail "Jalankan sebagai root (sudo -i atau su -)."
}

# detect_os
# Mengisi variabel global: OS_ID (debian|ubuntu|linuxmint|unknown),
# OS_VERSION (contoh 12, 22.04), OS_CODENAME (bookworm, jammy).
# Mengembalikan 0 bila OS yang didukung, 1 bila tidak.
detect_os() {
	[ -r /etc/os-release ] || return 1
	# shellcheck disable=SC1091
	. /etc/os-release
	OS_ID="${ID:-unknown}"
	OS_VERSION="${VERSION_ID:-unknown}"
	OS_CODENAME="${VERSION_CODENAME:-unknown}"

	case "$OS_ID" in
		debian)
			case "$OS_VERSION" in
				11|12|13) return 0 ;;
				*) warn "Debian $OS_VERSION belum diuji. Lanjut dengan risiko sendiri."; return 0 ;;
			esac
			;;
		ubuntu)
			case "$OS_VERSION" in
				22.04|24.04) return 0 ;;
				*) warn "Ubuntu $OS_VERSION belum diuji. Lanjut dengan risiko sendiri."; return 0 ;;
			esac
			;;
		linuxmint)
			# Mint punya VERSION_CODENAME sendiri; PPA ondrej/php mendukung Ubuntu yang
			# jadi basisnya. UBUNTU_CODENAME tersedia di /etc/os-release Mint.
			[ -n "${UBUNTU_CODENAME:-}" ] && OS_CODENAME="$UBUNTU_CODENAME"
			warn "Linux Mint terdeteksi; menganggap basis Ubuntu $OS_CODENAME."
			return 0
			;;
		*)
			return 1
			;;
	esac
}

# is_debian_based / is_ubuntu_based
is_debian_based() { [ "$OS_ID" = "debian" ]; }
is_ubuntu_based() { [ "$OS_ID" = "ubuntu" ] || [ "$OS_ID" = "linuxmint" ]; }

# apt_install <paket...>
# apt-get install yang tidak interaktif dan tidak merekomendasikan paket ekstra.
# Mudah diulang (idempotent: paket yang sudah ada tidak diubah).
apt_install() {
	[ "$#" -gt 0 ] || return 0
	export DEBIAN_FRONTEND=noninteractive
	apt-get install -y --no-install-recommends \
		-o Dpkg::Options::=--force-confdef \
		-o Dpkg::Options::=--force-confold \
		"$@"
}

# apt_ensure <paket...>
# Cek tiap paket dengan dpkg-query. Hanya install kalau ada yang belum terpasang.
apt_ensure() {
	local missing=() p
	for p in "$@"; do
		if ! dpkg-query -W -f='${db:Status-Abbrev}' "$p" 2>/dev/null | grep -q '^ii'; then
			missing+=("$p")
		fi
	done
	if [ "${#missing[@]}" -eq 0 ]; then
		return 0
	fi
	apt_install "${missing[@]}"
}

# apt_repo_add_keyring <url-key> <path-keyring> [fingerprint-expected]
# Unduh kunci GPG publik dari URL lalu simpan dalam bentuk dearmored (.gpg).
# Fingerprint opsional: jika diisi, periksa hasil gpg --show-keys benar-benar cocok.
apt_repo_add_keyring() {
	local url="$1" dst="$2" fp="${3:-}" tmp fp_actual
	tmp="$(mktemp)"
	curl -fsSL "$url" -o "$tmp" || { rm -f "$tmp"; return 1; }
	gpg --dearmor <"$tmp" >"$dst.tmp" \
		|| { rm -f "$tmp" "$dst.tmp"; return 1; }
	mv "$dst.tmp" "$dst"
	chmod 0644 "$dst"
	if [ -n "$fp" ]; then
		fp_actual="$(gpg --show-keys --with-colons "$dst" 2>/dev/null | awk -F: '$1=="fpr" {print $10; exit}')"
		[ "$fp_actual" = "$fp" ] \
			|| { rm -f "$tmp" "$dst"; return 1; }
	fi
	rm -f "$tmp"
	return 0
}

# write_if_changed <path> <content-stdin>
# Tulis ke path hanya bila isi berbeda. Mengembalikan 0 jika file diubah,
# 1 jika sudah sama. Dipakai untuk unit systemd supaya `systemctl daemon-reload`
# tidak perlu dijalankan kalau tidak ada perubahan.
write_if_changed() {
	local path="$1" tmp
	tmp="$(mktemp)"
	cat >"$tmp"
	if [ -f "$path" ] && cmp -s "$tmp" "$path"; then
		rm -f "$tmp"
		return 1
	fi
	install -Dm 0644 "$tmp" "$path"
	rm -f "$tmp"
	return 0
}

# wait_http <url> [kode-diharapkan] [detik]
# Tunggu sampai URL mengembalikan status HTTP yang diharapkan, maksimal N detik.
wait_http() {
	local url="$1" code="${2:-200}" max="${3:-20}" i actual
	for i in $(seq 1 "$max"); do
		actual="$(curl -s -o /dev/null -w '%{http_code}' --max-time 3 "$url" || true)"
		[ "$actual" = "$code" ] && return 0
		sleep 1
	done
	return 1
}
