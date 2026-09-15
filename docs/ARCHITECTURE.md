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
- **Template Fase 3:** `modern` memakai referensi VitaeKit Modern (sans-serif, navy `#1e40af` underline, A4 print CSS): https://vitaekit.com/resume-templates/modern. `classic` memakai referensi LumiCV Minimal (whitespace, `border-b-[1.5px] border-slate-900`, monochrome): https://lumicv.com/resume-templates/minimal. `neon` memakai referensi HTML internal `docs/gemini-code-1788187543370.html`: dokumen putih satu kolom, teks `#111`/`#444`, divider mint `#6ee7b7`, header kiri dengan grid kontak responsif, serta foto persegi opsional. Neon tidak memakai QR atau border luar. Body ketiga template tetap berurutan secara linear untuk pembacaan ATS; Sertifikat section sendiri setelah Proyek (heading standar Sertifikasi/Certificates). Skills pisah `Hard skills:` / `Soft skills:` di semua template. LinkedIn/Website/GitHub dukung `www.` tanpa scheme (normalisasi `https://` di `StoreCvRequest`). IPK di dalam Education (angka bold, label normal), Organisasi section terpisah. Foto opsional hanya dipakai Neon; di-upload via **Cloudinary signed upload** (endpoint `POST /api/v1/upload-signature` menandatangani, browser langsung kirim ke Cloudinary, hasil `secure_url` disimpan ke `personal.photo`) — `api_secret` tidak pernah bocor ke klien (config `config/cloudinary.php` + `.env` `CLOUDINARY_*`).
- **Implementasi (single-source, 2026-08-31):** `CvPreview.vue` adalah satu-satunya sumber markup (router ke `templates/CvModern.vue`/`CvClassic.vue`/`CvNeon.vue`, 1 template = 1 file, header include masing-masing). `CvController@pdf` memeriksa pemilik CV, membuat HTML dari print shell dengan `window.__CV_DATA__` + `window.__CV_TEMPLATE__` + `window.__CV_LANGUAGE__`, lalu `PdfService` menjalankan `Browsershot::html($html)` dalam proses yang sama. Label section mengikuti bahasa pilihan (PRD F7) via `web/src/lib/cv-labels.ts` — konten user tidak diterjemahkan, hanya heading dokumen ID/EN (`Pengalaman Kerja` ↔ `Work Experience`, dst.). Konten user diterjemahkan secara terpisah di fitur duplikat & terjemahkan (fase-7 7b) via `App\Services\TranslationService` (Google gtx) → `POST /api/v1/cvs/{cv}/translate`. Ini menghindari request balik ke server Laravel saat server development hanya menangani satu request. A4, margin 14/16mm, background, dan `waitUntilNetworkIdle()` tetap diterapkan. Karena shell HTML disimpan Browsershot sebagai `file://` sementara, Chromium diberi `disable-web-security` dan `allow-file-access-from-files` agar ES module Vite dapat dimuat. Blade `resources/views/pdf/cv.blade.php` **dihapus**. Endpoint `GET /api/v1/cvs/{cv}/print` tetap signed dan hanya untuk inspeksi shell internal, bukan jalur render PDF. Nambah template = tambah 1 entry `CV_TEMPLATES` + 1 file `templates/CvNamaBaru.vue` + 1 cabang di `CvPreview.vue` `comp` computed, PDF otomatis ikut tanpa duplikasi.
- **Paginasi (2026-09-08):** print CSS di `CvPreview.vue` menambah `header, section { break-inside: avoid; page-break-inside: avoid }` sehingga PDF tidak memotong judul section atau isi entry di tengah antar halaman (section yang tidak muat pindah utuh ke halaman berikutnya). Di editor (`CvFormView.vue`), `CvPreview` dipakai dengan prop `paged` → preview multi-halaman yang emulasi hasil PDF: lebar konten 673px (178mm @96dpi) dan tinggi halaman 1017px (269mm) identik area konten print, break hanya di batas `header`/`section` (sama seperti `break-inside: avoid`), tiap lembar dirender sebagai kertas A4 794×1123px dengan margin putih 61px/53px dan di-`scale` agar muat panel. Wrapper paged diberi ukuran eksplisit hasil scale (`width = 794×scale`, `height = (N×1123 + gap)×scale`, 2026-09-09) sehingga tidak ada sisa ruang kanan/bawah saat di-zoom-out. Landing (`HomeView`) dan shell print tetap non-paged (`compact`). Detail → [PDF_SINGLE_SOURCE_PLAN.md](phases/PDF_SINGLE_SOURCE_PLAN.md).
- **Print shell:** `web/print.html` + `web/src/print-main.ts` (mount `CvPreview` dari `window.__CV_DATA__`, tanpa router/Pinia). `vite.config.ts` multi-input (`main` + `print`). Dev: `CvController@print` deteksi Vite dev (`@vite/client` 200) lalu memakai minimal shell (`/src/print-main.ts` via Vite). Produksi memakai `web/dist/print.html` dan me-rewrite URL aset ke `FRONTEND_URL`. Detail rencana → [PDF_SINGLE_SOURCE_PLAN.md](phases/PDF_SINGLE_SOURCE_PLAN.md) (status: terealisasi).
- **Link dokumen CV netral (2026-09-13):** print CSS `CvPreview.vue` sebelumnya memakai `a { color: inherit !important; text-decoration: none !important }`. `!important` itu mengalahkan semua class warna Tailwind pada `<a>`, jadi di Browsershot (print media) link jatuh ke warna parent `text-slate-600` = `#45556c` dan underline-nya hilang. Preview di layar dan PDF karena itu beda tepat di link. Perbaikan: aturan dipersempit ke `a:not([class])` dan `!important` dicabut, sehingga link tanpa class (konten user) tetap dinetralkan sementara link template CV menerapkan warnanya. Link template sekarang memakai ink netral (`text-slate-900` `#0f172b` untuk modern/classic, `text-[#111]` untuk neon) dengan underline permanen `decoration-slate-300`, dan data pribadi non-link di header (email, telepon, alamat) ikut naik ke ink yang sama agar bobot visualnya setara dengan link. Verifikasi lewat PDF asli: warna di PDF `#0f172b`/`#111111` dengan garis underline `#cad5e2`/`#9ca3af` benar-benar ada sebagai vector. Media `screen` dan `print` kini menghasilkan warna identik, jadi preview = PDF. Navy tetap dipakai di UI app (CTA, badge), bukan di dokumen CV.

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
│   ├── lib/                 # cv-templates.ts (token template), cv-validation.ts (aturan wajib + peta error), utils.ts
│   ├── components/
│   │   ├── cv/              # CvForm (shell), CvPreview, form/, steps/, sections/
│   │   └── ui/              # shadcn-vue (badge, button, card)
│   └── router/index.ts
└── vite.config.ts           # proxy /api → localhost:8000
```

Detail `components/cv/` (refactor 2026-08-31, lihat [REFACTOR_PLAN.md](phases/REFACTOR_PLAN.md) + 1-template-1-file):

```
components/cv/
├── CvForm.vue              # shell: stepper nav 10 langkah + state, validasi submit, penanda error per step
├── form/                   # FormInput (dukung error inline + aria-invalid/aria-describedby),
│                           # FormTextarea, FormSelect, FormLabel (1 sumber kelas input)
├── steps/                  # 10 langkah: Meta, Personal, Summary, Experience, Education,
│                           # Organization, Skills, Projects, Certificates, Other
├── CvPreview.vue           # router: pilih CvModern/CvClassic/CvNeon via comp computed
├── templates/              # 1 template = 1 file (header include masing-masing)
│   ├── CvModern.vue        # single-column, navy accent
│   ├── CvClassic.vue       # single-column, serif, center header, split otherMode
│   └── CvNeon.vue          # single-column, mint divider, foto persegi opsional
└── sections/               # PreviewSection, EntryRow, BulletList (shared)

lib/cv-templates.ts         # token per template: font, headerAlign, h1Class, linkClass, otherMode, layout, accent, hasBorder, hasQr
lib/cv-validation.ts        # REQUIRED_FIELDS + ENTRY_RULES (cermin StoreCvRequest), pruneEmptyEntries, collectMissing, mapServerErrors
```

### Umpan balik validasi (2026-09-15)

Error validasi field **selalu inline**, tidak lewat toast. Alasan: toast auto-dismiss sebelum pengguna selesai membaca dan tidak terikat ke field, sehingga pengguna harus mencocokkan pesan dengan field secara manual (NN/g _10 Design Guidelines for Reporting Errors in Forms_; js-form-validation.com _Inline vs Toast vs Modal_). Pembagian kanal:

| Jenis                                            | Kanal                                                       | Sifat                      |
| ------------------------------------------------ | ----------------------------------------------------------- | -------------------------- |
| Field wajib / format (422 field-level)           | Inline di bawah field + `aria-invalid` + `aria-describedby` | Persist sampai field valid |
| Hasil operasi (sukses simpan draft, network/500) | Toast `role="status"` / `role="alert"`                      | Transien (2,6 detik)       |

Detail perilaku:

- Submit memanggil `prepareSubmit()` di `CvForm`: **prune entri kosong dulu**, baru validasi.
- Entri berulang (pengalaman/pendidikan/organisasi/sertifikat/proyek) yang seluruh field wajibnya kosong **dibuang otomatis** — "klik + Tambah lalu batal" bukan kesalahan. Entri yang terisi sebagian tetap dipertahankan dan memunculkan error inline.
- Kalau ada yang kurang, pengguna **dipindah ke step pemilik error pertama**, dengan fokus ke heading step (bukan langsung ke input) supaya perpindahan terbaca, bukan lompatan diam. Kalau error ada di step yang sedang aktif, fokus langsung ke field invalid pertama.
- Stepper menandai step bermasalah dengan `!` merah. Penanda baru muncul **setelah percobaan simpan pertama**, supaya field kosong tidak dihukum sebelum pengguna diberi tahu apa pun.
- Setelah percobaan simpan, error dihapus begitu field diperbaiki (re-validasi live).
- Payload `422` dari server dipetakan ke kunci field sisi klien lewat `mapServerErrors` (awalan `data.` dibuang), jadi pesan tidak pernah tampil mentah. `error` banner hanya dipakai untuk kegagalan operasional (network/5xx) — di situ pesan mentah berguna untuk debug.

### Simpan draft parsial (`?draft=1`, 2026-09-15)

Tombol `Simpan Draft` menyimpan progres setengah jadi; `Simpan CV` memvalidasi seperti data final. Pemisahan ini mengikuti panduan autosave (uxpatternsguide.com _Autosave form_: "clear separation between autosaved draft progress and final submit"):

- **Klien:** `draftSave()` memanggil `formRef.pruneEntries()` lebih dulu, lalu `checkFormats()`, baru `POST/PUT` dengan `?draft=1`. `pruneEntries()` dipisah dari `prepareSubmit()` agar draft bisa membuang entri kosong tanpa memicu validasi wajib.
- **Server:** `StoreCvRequest::isDraft()` (public, dipanggil juga `CvController`) melonggarkan ruleset — `required`/`required_with` → `nullable`, tipe/`max`/`in`/`regex`/`email` **tetap**. `CvController::payload()` mengisi `title` placeholder `"CV Tanpa Judul"` bila kosong (kolom NOT NULL).
- **Format ≠ wajib:** draft melonggarkan _harus diisi_, **bukan** format. Email harus email valid dan telepon harus angka + simbol (`+ - ( ) . spasi`, min 7 digit) bila terisi — dicek dua sisi (`INVALID_FORMATS` klien + rule `regex`/`email` server). Format salah memblokir Simpan Draft, Simpan, dan Download.
- **Error draft:** `422` → `errors` dipetakan inline (sama seperti submit) + toast ringkas; kegagalan operasional → toast `"Gagal menyimpan draft. Coba lagi."`. Tidak pernah menampilkan `message` mentah. `StoreCvRequest::messages()`/`attributes()` menyediakan pesan Indonesia (locale aplikasi `en`, jadi pesan default Inggris harus ditimpa eksplisit).

### Gate Download PDF (2026-09-15)

Tombol `Download PDF` di editor menolak mengunduh CV yang belum lengkap. Pola ini mengikuti praktik builder resume (cth. resume-forge, cv-embed: skor kelengkapan + error inline sebelum ekspor) dan prinsip aksesibilitas "errors persist and are tied to fields, not transient toasts".

- **Cek sinkron dulu:** `downloadPdf()` memanggil `formRef.isComplete()` — fungsi murni (tanpa prune/efek samping) yang kini juga memeriksa **format** email/telepon (`collectInvalid`) — sebelum menyentuh `window.open`. Kalau kurang atau format salah, **window tidak pernah dibuka** (bukan dibuka lalu ditutup, agar tidak ada tab berkelip terbuka-tutup).
- **Feedback saat gagal:** `prepareSubmit()` dijalankan agar error inline + banner persisten muncul dan pengguna dipindah ke step bermasalah, ditambah toast ringkas `"Lengkapi dulu sebelum mengunduh."`.
- **Saat lengkap:** `win.open(url, "_blank")` dipanggil **sinkron** dari handler klik (tidak lewat `await`) agar lolos kebijakan popup-blocker browser.
- **Guard server (Opsi B, 2026-09-15):** `Cv::missingForPdf()` (dipakai `CvController::pdf()`) mengembalikan `422` + `{message, errors}` bila `title`/`data.personal.{name,email,phone,address}` kosong, format email/telepon salah, **atau** ada entri berulang setengah jadi. Entri dicek lewat `Cv::ENTRY_FIELDS` — cerminan `ENTRY_RULES` klien dan `required_with` di `StoreCvRequest`. Prinsip: apa yang tak bisa disimpan, tak bisa diunduh.
- **Dashboard (`PDF`):** tombol di-disable lewat `cv.is_complete` (boolean ringan di `CvResource`, tersedia termasuk di list) + `title` tooltip. Karena `missingForPdf()` adalah satu-satunya sumber kebenaran, status disabled konsisten dengan guard server. `downloadPdf()` tetap `fetch` endpoint sebagai jaring pengaman: `422` → pesan error halaman (tanpa tab), `200` → unduh blob lewat anchor + object URL (bukan `window.open`, tahan popup-blocker).
- **Tombol disabled & kontras:** gaya disabled memakai warna eksplisit (`disabled:text-slate-500 dark:disabled:text-foreground/50`), **bukan** `opacity` global — opacity memudarkan teks sekaligus dan jatuh di bawah WCAG AA di dark mode (terukur ~2.2:1 pada 0.4). Dengan warna eksplisit: light `slate-500 #64748b` = 4.55:1, dark `foreground/50` = 4.68:1.

## 4. Keamanan

- Validasi input dua sisi: validator klien (`web/src/lib/cv-validation.ts`) + Form Request (BE). BE adalah sumber kebenaran; validator klien hanya memberi umpan balik lebih awal dan tidak menggantikan validasi server.
- Rate limit global API + khusus endpoint AI.
- CORS dibatasi ke origin frontend saja.
- Endpoint AI memvalidasi ukuran payload (CV data ≤ ~50 KB).
