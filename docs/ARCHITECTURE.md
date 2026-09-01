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
- **Template Fase 3:** `modern` memakai referensi VitaeKit Modern (sans-serif, navy `#1e40af` underline, A4 print CSS): https://vitaekit.com/resume-templates/modern. `classic` memakai referensi LumiCV Minimal (whitespace, `border-b-[1.5px] border-slate-900`, monochrome): https://lumicv.com/resume-templates/minimal. `neon` memakai referensi HTML internal `docs/gemini-code-1788187543370.html`: dokumen putih satu kolom, teks `#111`/`#444`, divider mint `#6ee7b7`, header kiri dengan grid kontak responsif, serta foto persegi opsional. Neon tidak memakai QR atau border luar. Body ketiga template tetap berurutan secara linear untuk pembacaan ATS; grid responsif Neon hanya dipakai untuk kontak dan blok Bahasa/Sertifikat. Skills pisah `Hard skills:` / `Soft skills:` di semua template. LinkedIn/Website/GitHub dukung `www.` tanpa scheme (normalisasi `https://` di `StoreCvRequest`). IPK di dalam Education, Organisasi section terpisah. Foto opsional hanya dipakai Neon; di-upload via **Cloudinary signed upload** (endpoint `POST /api/v1/upload-signature` menandatangani, browser langsung kirim ke Cloudinary, hasil `secure_url` disimpan ke `personal.photo`) — `api_secret` tidak pernah bocor ke klien (config `config/cloudinary.php` + `.env` `CLOUDINARY_*`).
- **Implementasi (single-source, 2026-08-31):** `CvPreview.vue` adalah satu-satunya sumber markup (router ke `templates/CvModern.vue`/`CvClassic.vue`/`CvNeon.vue`, 1 template = 1 file, header include masing-masing). `CvController@pdf` memeriksa pemilik CV, membuat HTML dari print shell dengan `window.__CV_DATA__` + `window.__CV_TEMPLATE__`, lalu `PdfService` menjalankan `Browsershot::html($html)` dalam proses yang sama. Ini menghindari request balik ke server Laravel saat server development hanya menangani satu request. A4, margin 14/16mm, background, dan `waitUntilNetworkIdle()` tetap diterapkan. Karena shell HTML disimpan Browsershot sebagai `file://` sementara, Chromium diberi `disable-web-security` dan `allow-file-access-from-files` agar ES module Vite dapat dimuat. Blade `resources/views/pdf/cv.blade.php` **dihapus**. Endpoint `GET /api/v1/cvs/{cv}/print` tetap signed dan hanya untuk inspeksi shell internal, bukan jalur render PDF. Nambah template = tambah 1 entry `CV_TEMPLATES` + 1 file `templates/CvNamaBaru.vue` + 1 cabang di `CvPreview.vue` `comp` computed, PDF otomatis ikut tanpa duplikasi.
- **Print shell:** `web/print.html` + `web/src/print-main.ts` (mount `CvPreview` dari `window.__CV_DATA__`, tanpa router/Pinia). `vite.config.ts` multi-input (`main` + `print`). Dev: `CvController@print` deteksi Vite dev (`@vite/client` 200) lalu memakai minimal shell (`/src/print-main.ts` via Vite). Produksi memakai `web/dist/print.html` dan me-rewrite URL aset ke `FRONTEND_URL`. Detail rencana → [PDF_SINGLE_SOURCE_PLAN.md](phases/PDF_SINGLE_SOURCE_PLAN.md) (status: terealisasi).

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
└── config/                  # app.frontend_url, CORS, Sanctum, dan rate limiter

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

Detail `components/cv/` (refactor 2026-08-31, lihat [REFACTOR_PLAN.md](phases/REFACTOR_PLAN.md) + 1-template-1-file):

```
components/cv/
├── CvForm.vue              # shell: stepper nav 9 langkah + state (~150 baris)
├── form/                   # FormInput, FormTextarea, FormSelect, FormLabel (1 sumber kelas input)
├── steps/                  # 9 langkah: Meta, Personal, Summary, Experience, Education,
│                           # Organization, Skills, Projects, Other
├── CvPreview.vue           # router: pilih CvModern/CvClassic/CvNeon via comp computed
├── templates/              # 1 template = 1 file (header include masing-masing)
│   ├── CvModern.vue        # single-column, navy accent
│   ├── CvClassic.vue       # single-column, serif, center header, split otherMode
│   └── CvNeon.vue          # single-column, mint divider, foto persegi opsional
└── sections/               # PreviewSection, EntryRow, BulletList (shared)

lib/cv-templates.ts         # token per template: font, headerAlign, h1Class, linkClass, otherMode, layout, accent, hasBorder, hasQr
```

## 4. Keamanan

- Validasi input dua sisi: VeeValidate (FE) + Form Request (BE). BE adalah sumber kebenaran.
- Rate limit global API + khusus endpoint AI.
- CORS dibatasi ke origin frontend saja.
- Endpoint AI memvalidasi ukuran payload (CV data ≤ ~50 KB).
