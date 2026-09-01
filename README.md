# ResumeKan

Generator CV ATS-friendly berbasis AI. Isi form → pilih template → download PDF.

## Stack

| Layer    | Teknologi                                                  |
| -------- | ---------------------------------------------------------- |
| Backend  | Laravel 13 (API-only), PHP 8.3+, Sanctum (SPA cookie auth) |
| Frontend | Vue 3 + TypeScript + Vite, Pinia, Vue Router               |
| UI       | Tailwind CSS v4, shadcn-vue                                |
| Animasi  | @vueuse/motion                                             |
| Database | SQLite (lokal) → Neon Postgres (produksi)                  |
| PDF      | Spatie Browsershot (headless Chrome)                       |
| AI       | AI gateway OpenAI-compatible via HTTP client biasa         |
| Foto     | Cloudinary (signed upload dari browser)                    |

## Struktur Monorepo

```
ResumeKan/
├── api/            # Laravel 13 (backend API)
├── web/            # Vue 3 SPA (frontend)
└── docs/           # Dokumen sebelum development
```

## Menjalankan (harian)

```bash
# Terminal 1 — API http://127.0.0.1:8000
cd api && php artisan serve
# Windows Herd Lite: export PATH="/c/Users/ASUS TUF/.config/herd-lite/bin:$PATH" dulu

# Terminal 2 — Web http://127.0.0.1:5173
cd web && pnpm dev
```

> Detail lengkap (env, prasyarat, troubleshooting) → [Setup](docs/SETUP.md).

## Dokumentasi

- [Setup](docs/SETUP.md) — prasyarat & environment
- [PRD](docs/PRD.md) — apa yang dibangun
- [Architecture](docs/ARCHITECTURE.md) — keputusan teknis (ADR)
- [API Spec](docs/API_SPEC.md) — kontrak endpoint
- [Data Model](docs/DATA_MODEL.md) — skema database & JSON CV
- [Roadmap](docs/ROADMAP.md) — urutan pengerjaan (±5 hari)
- [Testing](docs/TESTING.md) — alur uji API (curl), SPA (browser), & otomatis
- [Progress per Fase](docs/phases/) — ringkasan status & pelajaran tiap fase
