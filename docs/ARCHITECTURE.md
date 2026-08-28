# Architecture — ResumeKan

> Keputusan teknis dan alasannya. Format ADR ringkas.

## 1. Gambaran Umum

```
┌─────────────┐  HTTP (cookie Sanctum)  ┌──────────────────┐
│  web/ (SPA) │ ───────────────────────▶│  api/ (Laravel)  │──▶ SQLite / Neon Postgres
│  Vue 3+Vite │ ◀───────────────────────│  API-only        │──▶ Gemini API
└─────────────┘   JSON                  └────────┬─────────┘
                                                 │ Browsershot
                                                 ▼
                                          headless Chrome → PDF
```

## 2. Keputusan (ADR)

### ADR-1: Decoupled SPA, bukan Inertia

- **Keputusan:** Laravel murni API (`api/`), Vue SPA terpisah (`web/`).
- **Alasan:** Requirement user eksplisit mau Vue sebagai FE terpisah; Inertia mengunci ke Blade + Vue di dalam Laravel.

### ADR-2: Sanctum mode SPA (cookie), bukan JWT

- **Keputusan:** `laravel/sanctum` dengan stateful cookie.
- **Alasan:** First-party SPA di domain sendiri; tanpa refresh-token management, tanpa package tambahan.

### ADR-3: CV disimpan sebagai JSONB tunggal

- **Keputusan:** Tabel `cvs` dengan kolom `data` (JSON) menampung seluruh isi CV.
- **Alasan:** Skema CV sering berubah (tambah field). Normalisasi ke 6–7 tabel hanya menyulitkan. Query selalu by `id` + `user_id`, tidak perlu filter dalam JSON.
- **Konsekuensi:** Validasi struktur dilakukan di Form Request (bukan DB constraint).

### ADR-4: PDF via Browsershot server-side

- **Keputusan:** Spatie Browsershot render HTML template yang sama dengan preview → PDF.
- **Alasan:** CV = HTML/CSS; hasil identik dengan preview. DomPDF/wkhtmltopdf rusak pada Tailwind modern (flex/grid/oklch).
- **Prasyarat deploy:** binary Chromium tersedia di server.
- **Template Fase 3:** `modern` = VitaeKit Modern (sans-serif, navy `#1e40af` underline, A4 print CSS) — https://vitaekit.com/resume-templates/modern · `classic` = LumiCV Minimal (whitespace, `border-b-[1.5px] border-slate-900` 8 section, monochrome) — https://lumicv.com/resume-templates/minimal. Keduanya single-column ATS-friendly, HTML/CSS murni yang sama untuk preview & PDF. Skills pisah `Hard skills:` / `Soft skills:` di kedua template. LinkedIn/Website/GitHub dukung `www.` tanpa scheme (normalisasi `https://` di `StoreCvRequest`). IPK di dalam Education, Organisasi section terpisah.

### ADR-5: Gemini via `Http::post()`, tanpa SDK

- **Keputusan:** Satu class `GeminiService` membungkus HTTP call.
- **Alasan:** Satu endpoint REST; SDK resmi hanya menambah dependency untuk hal yang 15 baris curl bisa.

### ADR-6: SQLite lokal → Neon Postgres produksi

- **Keputusan:** Driver via `.env`; migration ditulis agnostik.
- **Alasan:** Zero-setup lokal; Neon free tier permanen untuk produksi.

## 3. Struktur Folder Rencana

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # CvController, AuthController, AiController
│   │   ├── Requests/        # StoreCvRequest (validasi skema JSON)
│   │   └── Resources/       # CvResource
│   ├── Services/
│   │   ├── GeminiService.php
│   │   └── PdfService.php
│   └── Models/              # User, Cv
├── routes/api.php
├── database/migrations/
└── config/cv.php            # batas 10 CV, rate limit AI

web/
├── src/
│   ├── api/                 # fetch wrapper + endpoint functions
│   ├── stores/              # auth.ts, cv.ts (Pinia)
│   ├── views/               # Landing, BuatCv, PreviewCv, Dashboard, Login, Register
│   ├── components/
│   │   ├── cv/              # CvForm, CvPreview, template modern/classic
│   │   └── ui/              # shadcn-vue
│   └── router/index.ts
└── vite.config.ts           # proxy /api → localhost:8000
```

## 4. Keamanan

- Validasi input dua sisi: VeeValidate (FE) + Form Request (BE). BE adalah sumber kebenaran.
- Rate limit global API + khusus endpoint AI.
- CORS dibatasi ke origin frontend saja.
- Endpoint AI memvalidasi ukuran payload (CV data ≤ ~50 KB).
