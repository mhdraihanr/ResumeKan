#!/usr/bin/env bash
# lib/node.sh — pasang Node.js 22 + pnpm 10 lewat Corepack.
#
# Dipakai dua tempat:
#   * api/node_modules (Puppeteer) butuh npm untuk `npm ci` di api/;
#   * fallback build frontend lokal butuh pnpm.
# Runner GitHub Actions memakai Node 22, jadi versi mayor ini yang diuji.
#
# Idempotent: tidak memasang ulang yang sudah ada.

NODE_MAJOR="${NODE_MAJOR:-22}"
PNPM_MAJOR="${PNPM_MAJOR:-10}"

ensure_node() {
	require_root
	if have node && have npm && have pnpm; then
		log "Node $(node --version), npm $(npm --version), pnpm $(pnpm --version) sudah ada."
		return 0
	fi

	if ! have node || ! have npm; then
		step "Pasang Node.js ${NODE_MAJOR}.x (NodeSource)"
		apt_ensure ca-certificates curl gnupg
		curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash - >/dev/null 2>&1 \
			|| fail "Gagal menambahkan repo NodeSource ${NODE_MAJOR}.x"
		apt_install nodejs
	fi

	if ! have pnpm; then
		step "Pasang pnpm ${PNPM_MAJOR} lewat Corepack"
		corepack enable >/dev/null 2>&1 || fail "corepack enable gagal."
		corepack prepare "pnpm@${PNPM_MAJOR}" --activate >/dev/null 2>&1 \
			|| fail "Gagal mengaktifkan pnpm@${PNPM_MAJOR}."
	fi

	log "Node $(node --version), npm $(npm --version), pnpm $(pnpm --version)"
}
