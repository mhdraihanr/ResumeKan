# ResumeKan

Generator CV ATS-friendly berbasis AI. Isi form → pilih template → download PDF.

## Stack

| Layer    | Teknologi                                                  |
| -------- | ---------------------------------------------------------- |
| Backend  | Laravel 12 (API-only), PHP 8.3+, Sanctum (SPA cookie auth) |
| Frontend | Vue 3 + TypeScript + Vite, Pinia, Vue Router               |
| UI       | Tailwind CSS v4, shadcn-vue                                |
| Animasi  | @vueuse/motion                                             |
| Database | SQLite (lokal) → Neon Postgres (produksi)                  |
| PDF      | Spatie Browsershot (headless Chrome)                       |
| AI       | Gemini API via HTTP client biasa                           |

## Struktur Monorepo

```
ResumeKan/
├── api/            # Laravel 12 (backend API)
├── web/            # Vue 3 SPA (frontend)
└── docs/           # Dokumen sebelum development
```

## Menjalankan (setelah scaffold)

```bash
# Backend
cd api && composer install && cp .env.example .env
php artisan migrate && php artisan serve

# Frontend
cd web && pnpm install && pnpm run dev
```

## Dokumentasi

- [Setup](docs/SETUP.md) — prasyarat & environment
- [PRD](docs/PRD.md) — apa yang dibangun
- [Architecture](docs/ARCHITECTURE.md) — keputusan teknis (ADR)
- [API Spec](docs/API_SPEC.md) — kontrak endpoint
- [Data Model](docs/DATA_MODEL.md) — skema database & JSON CV
- [Roadmap](docs/ROADMAP.md) — urutan pengerjaan (±5 hari)
- [Testing](docs/TESTING.md) — alur uji API (curl), SPA (browser), & otomatis
- [Progress per Fase](docs/phases/) — ringkasan status & pelajaran tiap fase
