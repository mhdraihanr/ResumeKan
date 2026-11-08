#!/usr/bin/env bash
# lib/pdf-browser.sh — pasang browser untuk Browsershot (ekspor PDF).
#
# Alasan beda per OS:
#   * Debian 12: paket `chromium` tersedia di repo resmi dan langsung jalan.
#   * Ubuntu 22.04+: paket `chromium` adalah paket dummy (snap); snap tidak
#     jalan baik di server headless kecil, jadi jatuh ke Google Chrome stable
#     yang path-nya memang dicari api/app/Services/PdfService.php.
#
# Diuji dengan api/app/Services/PdfService.php: mencari /usr/bin/chromium,
# /usr/bin/chromium-browser, /usr/bin/google-chrome, /usr/bin/google-chrome-stable.

PDF_FONTS="fonts-liberation fonts-noto-core fonts-dejavu-core"

_browser_present() {
	command -v chromium >/dev/null 2>&1 \
		|| command -v chromium-browser >/dev/null 2>&1 \
		|| command -v google-chrome >/dev/null 2>&1 \
		|| command -v google-chrome-stable >/dev/null 2>&1
}

_install_google_chrome() {
	step "Pasang Google Chrome stable (fallback Chromium tidak tersedia)"
	apt_ensure ca-certificates curl gnupg
	local keyring=/usr/share/keyrings/google-chrome.gpg
	apt_repo_add_keyring https://dl.google.com/linux/linux_signing_key.pub "$keyring" \
		|| fail "Gagal mengunduh kunci Google Chrome."
	printf 'deb [arch=amd64 signed-by=%s] http://dl.google.com/linux/chrome/deb/ stable main\n' "$keyring" \
		>/etc/apt/sources.list.d/google-chrome.list
	chmod 0644 /etc/apt/sources.list.d/google-chrome.list
	apt-get update -y >/dev/null || fail "apt-get update gagal setelah repo Google Chrome."
	apt_install google-chrome-stable
}

ensure_pdf_browser() {
	if [ "${SKIP_CHROMIUM:-0}" = "1" ]; then
		log "Browser PDF dilewati (SKIP_CHROMIUM=1). Ekspor PDF tidak akan berjalan."
		return 0
	fi

	apt_install $PDF_FONTS

	if _browser_present; then
		log "Browser PDF sudah ada: $(command -v chromium 2>/dev/null || command -v chromium-browser 2>/dev/null || command -v google-chrome 2>/dev/null || command -v google-chrome-stable)"
		return 0
	fi

	if is_debian_based; then
		step "Pasang Chromium (Debian)"
		if apt_install chromium >/dev/null 2>&1; then
			log "Chromium dipasang dari repo Debian."
		else
			warn "Paket chromium tidak bisa dipasang di Debian; beralih ke Google Chrome."
			_install_google_chrome
		fi
	else
		step "Ubuntu: paket chromium adalah snap dummy; memakai Google Chrome stable"
		_install_google_chrome
	fi

	_browser_present || fail "Tidak ada Chromium/Chrome setelah instalasi. PDF tidak akan jalan."
	# Font tambahan agar PDF tidak menjadi kotak-kotak.
	apt_ensure fonts-noto-cjk || true
	log "Browser PDF siap."
}
