export interface PublicConfig {
  /** Site key Turnstile (publik, aman dibaca siapa saja). */
  turnstile_site_key: string;
  /** Cap waktu terenkripsi dari server, dipakai mendeteksi submit instan. */
  spam_token: string;
}

/**
 * Diambil saat halaman register dibuka. Token sengaja "menua" selama user
 * mengetik, itulah yang membuat jeda waktu di middleware punya arti.
 */
export async function fetchPublicConfig(): Promise<PublicConfig> {
  const res = await fetch("/api/v1/config", {
    headers: { Accept: "application/json" },
  });
  if (!res.ok) throw new Error("Gagal memuat konfigurasi keamanan.");
  return (await res.json()) as PublicConfig;
}
