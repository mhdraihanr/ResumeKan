# Architecture — ResumeKan

> Keputusan teknis dan alasannya. Format ADR ringkas.

## 1. Gambaran Umum

```
┌─────────────┐  HTTP (cookie Sanctum)  ┌──────────────────┐
│  web/ (SPA) │ ───────────────────────▶│  api/ (Laravel)  │──▶ SQLite / Neon Postgres
│  Vue 3+Vite │ ◀───────────────────────│  API-only        │──▶ AI Gateway (OpenAI-compatible)
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
- **Prasyarat deploy:** binary Chromium tersedia di server. Lokal: `PdfService` otomatis memakai Microsoft Edge (Chromium) via `useChrome()->setChromePath()`; fallback Puppeteer (`npm i puppeteer` di `api/`).
- **Template Fase 3:** `modern` = VitaeKit Modern (sans-serif, navy `#1e40af` underline, A4 print CSS) — https://vitaekit.com/resume-templates/modern · `classic` = LumiCV Minimal (whitespace, `border-b-[1.5px] border-slate-900` 8 section, monochrome) — https://lumicv.com/resume-templates/minimal. Keduanya single-column ATS-friendly, HTML/CSS murni yang sama untuk preview & PDF. Skills pisah `Hard skills:` / `Soft skills:` di kedua template. LinkedIn/Website/GitHub dukung `www.` tanpa scheme (normalisasi `https://` di `StoreCvRequest`). IPK di dalam Education, Organisasi section terpisah.
- **Implementasi:** `PdfService` render `resources/views/pdf/cv.blade.php` (Blade mandiri, CSS inline meniru markup `CvPreview.vue`) → A4, margin 14/16mm, `showBackground()`. Endpoint `GET /api/v1/cvs/{cv}/pdf` → `CvController@pdf` (owner check, `{nama}_CV.pdf`).
- **Sinkronisasi markup (2026-08-31):** Preview Vue dipecah jadi `sections/` (`PreviewSection`, `EntryRow`, `BulletList`) + token `web/src/lib/cv-templates.ts` (font, headerAlign, h1Class, linkClass, otherMode per template) — markup hasil render identik dengan sebelum refactor. Nambah template = tambah 1 entry `CV_TEMPLATES` (select form, toggle landing, preview otomatis ikut). Blade PDF tetap file mandiri; **perubahan layout CV wajib disinkronkan ke 2 tempat** (`CvPreview.vue` + `cv.blade.php`). Drift yang diketahui: header kontak Blade masih 1 baris (preview sudah 2 baris semantik sejak 2026-08-30) — perbaiki saat menyentuh PDF berikutnya. Opsi single-source (Browsershot load URL SPA print) ditunda: butuh route print + auth headless, terlalu besar untuk refactor tanpa perubahan perilaku.

### ADR-5: AI Gateway (OpenAI-compatible) via `Http::post()`, tanpa SDK

- **Keputusan:** Satu class `AiService` membungkus HTTP call ke gateway OpenAI-compatible (model ganti via `.env` `AI_MODEL`, tidak lock-in 1 provider). Prompt anti-slop: K1/K2/K3 fokus posisi dominan, proyek hanya techStack background, banned buzzwords, hanya fakta CV.
- **Alasan:** Satu endpoint `POST /v1/chat/completions` untuk semua model; ganti model cuma ganti string di `.env` tanpa ubah kode. Tanpa SDK, 15 baris `Http::post()` cukup. Prompt mengikuti antislop-copywriting + Exa ATS (40-60 kata, tools konkret, angka hanya jika ada di data, tanpa judul proyek).

### ADR-6: SQLite lokal → Neon Postgres produksi

- **Keputusan:** Driver via `.env`; migration ditulis agnostik.
- **Alasan:** Zero-setup lokal; Neon free tier permanen untuk produksi.

## 3. Struktur Folder

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # CvController, AuthController, AiController
│   │   ├── Requests/        # StoreCvRequest (validasi skema JSON)
│   │   └── Resources/       # CvResource
│   ├── Services/
│   │   ├── AiService.php
│   │   └── PdfService.php
│   └── Models/              # User, Cv
├── routes/api.php
├── database/migrations/
└── config/cv.php            # batas 10 CV, rate limit AI

web/
├── src/
│   ├── api/                 # fetch wrapper + endpoint functions
│   ├── stores/              # auth.ts, cv.ts (Pinia)
│   ├── views/               # HomeView, CvFormView, DashboardView, LoginView, RegisterView
│   ├── composables/         # useDarkMode.ts
│   ├── lib/                 # cv-templates.ts (token template), utils.ts
│   ├── components/
│   │   ├── cv/              # CvForm (shell), CvPreview, form/, steps/, sections/
│   │   └── ui/              # shadcn-vue (badge, button, card)
│   └── router/index.ts
└── vite.config.ts           # proxy /api → localhost:8000
```

Detail `components/cv/` (refactor 2026-08-31, lihat [REFACTOR_PLAN.md](phases/REFACTOR_PLAN.md)):

```
components/cv/
├── CvForm.vue              # shell: stepper nav 9 langkah + state (~150 baris)
├── form/                   # FormInput, FormTextarea, FormSelect, FormLabel (1 sumber kelas input)
├── steps/                  # 9 langkah: Meta, Personal, Summary, Experience, Education,
│                           # Organization, Skills, Projects, Other
├── CvPreview.vue           # 1 sumber section, token-driven
└── sections/               # PreviewSection, EntryRow, BulletList (dipakai semua template)

lib/cv-templates.ts         # token per template: font, headerAlign, h1Class, linkClass, otherMode
```

## 4. Keamanan

- Validasi input dua sisi: VeeValidate (FE) + Form Request (BE). BE adalah sumber kebenaran.
- Rate limit global API + khusus endpoint AI.
- CORS dibatasi ke origin frontend saja.
- Endpoint AI memvalidasi ukuran payload (CV data ≤ ~50 KB).
