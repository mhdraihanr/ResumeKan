# API Spec — ResumeKan

> Base URL: `/api/v1`. Auth: cookie Sanctum (`/api/v1/*` stateful).
> Response JSON, kecuali unduhan PDF dan shell print internal. Error JSON memakai `{ "message": string, "errors"?: { field: string[] } }`.

## Konvensi

- `401` belum login · `403` bukan pemilik resource · `404` tidak ada · `422` validasi · `429` rate limit · `502` kegagalan layanan hilir (render PDF / AI gateway / terjemahan).
- Semua route, selain auth, `GET /config`, dan shell print internal bertanda tangan, butuh login.
- **Tanpa auth selalu `401` JSON, bukan redirect (2026-10-02).** App ini API-only: request ke `api/*` tanpa cookie sesi valid membalas `401 { "message": "Unauthenticated." }`, tidak pernah `500 "Route [login] not defined."` maupun redirect. Detail akar masalah di [ARCHITECTURE.md §4](ARCHITECTURE.md).
- `422` membawa `errors` dengan kunci berpath JSON (`data.certificates.0.name`). Klien **tidak** menampilkan `message` mentahnya: `mapServerErrors` di `lib/cv-validation.ts` membuang awalan `data.` dan memetakan tiap kunci ke error inline pada field yang bersangkutan. `message` mentah hanya ditampilkan sebagai banner untuk kegagalan operasional (network/5xx) — lihat [ARCHITECTURE.md §4](ARCHITECTURE.md). Berlaku untuk submit final **dan** simpan draft (yang juga memakai jalur `errors` → inline, bukan toast pesan mentah).

## Auth

| Method | Path        | Body                                           | Response                |
| ------ | ----------- | ---------------------------------------------- | ----------------------- |
| GET    | `/config`   | —                                              | `{ turnstile_site_key, spam_token }` |
| POST   | `/register` | `name, email, password, password_confirmation, website, spam_token, cf-turnstile-response` | `201 { user }` + cookie |
| POST   | `/login`    | `email, password`                              | `{ user }` + cookie     |
| POST   | `/logout`   | —                                              | `204`                   |
| GET    | `/user`     | —                                              | `{ user }`              |

> **Anti-spam register (2026-10-04):** selain rate limit, `POST /register` dijaga middleware `turnstile`. Tiga field tambahan **wajib**:
> - `cf-turnstile-response` — token dari widget Turnstile; diverifikasi ke `siteverify` Cloudflare (secret dari `TURNSTILE_SECRET_KEY`). Token sekali pakai, ambil baru setelah submit gagal.
> - `website` — **honeypot**, harus string kosong; terisi → `422` (bot mengisi semua field).
> - `spam_token` — cap waktu terenkripsi dari `GET /config`; submit < 2 detik setelah halaman dibuka → `422` (submit instan).
>
> Gagal salah satu → `422 { "message": "..." }`. Widget Turnstile di-render hanya kalau `turnstile_site_key` tidak kosong, jadi environment tanpa key (dev/test) tetap bisa register.

> **Rate limit auth (2026-10-03):** `POST /register` dibatasi **dua lapis per IP** — **5/menit** (burst) dan **20/jam** (akumulasi), keduanya dari `config/security.php` via env `REGISTER_PER_MINUTE`/`REGISTER_PER_HOUR`. `POST /login` dibatasi dua lapis: **5/menit per email+IP** (batas utama) dan **10/menit per email** (cadangan, supaya satu akun yang diserang dari banyak IP tetap kena). Lewat batas → `429` + header `Retry-After`/`X-RateLimit-*`. Limiter ada di `AppServiceProvider::boot()`; kunci IP memakai `X-Real-IP` yang di-stabilkan `ResolveClientIp` (lihat [ARCHITECTURE.md §5](ARCHITECTURE.md)) — tanpa itu tiap request dianggap IP berbeda dan batas tidak pernah tercapai.

## Upload Foto (Cloudinary)

### `POST /upload-signature` (auth)

Mengembalikan credential & signature untuk signed upload langsung dari browser ke Cloudinary. `api_secret` tidak pernah dikirim ke klien.

```json
{
  "cloud_name": "dzqrr2ks",
  "api_key": "...",
  "timestamp": "1710000000",
  "signature": "sha1...",
  "folder": "cvs"
}
```

Klien lalu `POST` ke `https://api.cloudinary.com/v1_1/{cloud_name}/image/upload` dengan `file, api_key, timestamp, signature, folder` → response berisi `secure_url` yang disimpan ke `data.personal.photo`. `config/cloudinary.php` membaca `.env` `CLOUDINARY_CLOUD_NAME` / `CLOUDINARY_API_KEY` / `CLOUDINARY_API_SECRET`.

## CV

### `GET /cvs`

```json
{
  "data": [
    {
      "id": 1,
      "title": "CV Backend",
      "template": "modern",
      "language": "id",
      "updated_at": "..."
    }
  ]
}
```

> Sengaja tanpa `data` (berat); ambil detail per-CV.

### `POST /cvs`

```json
{
  "title": "CV Backend",
  "template": "modern",            // modern | classic | neon
  "language": "id",                // id | en
  "data": { ...CvData }            // lihat DATA_MODEL.md
}
```

→ `201 { cv }`. Gagal jika user sudah punya 10 CV → `422`.

**Mode draft (`?draft=1`)** — dipakai tombol `Simpan Draft`, yang menyimpan progres setengah jadi. `StoreCvRequest::isDraft()` mendeteksi flag dan melonggarkan ruleset: setiap `required`/`required_with` → `nullable`, sedangkan tipe/`max`/`in` tetap dicek bila field terisi. `title` kosong diisi placeholder `"CV Tanpa Judul"` oleh `CvController::payload()` (kolom `title` NOT NULL). Submit final **tanpa** flag tetap memakai ruleset ketat. Berlaku juga untuk `PUT /cvs/{id}?draft=1`.

> `data.projects` terstruktur: array objek `{ title, role, objective, techStack, link? }` (max 8, `link` opsional ≤500 dinormalisasi `https://`). `data.certificates` terstruktur: array objek `{ name, issuer, year, credentialId? }` (max 5, section sendiri). Nilai lama `string` masih diterima untuk keduanya (backward compat, dikonversi ke 1 item). `data.education[].gpa` opsional `≤10`, `data.education[].location` opsional, `data.education[].degree` = gelar & jurusan digabung (field `major` dihapus), `data.education[].achievements` opsional `≤1000` (bullet newline), `data.organizations` array max 5, `data.experiences[].employmentType` opsional `in: Full-time,Part-time,Internship,Contract,Freelance`. Field tipografi opsional: `data.fontFamily` (`string ≤50`, in: `default,inter,source-sans,lora,merriweather`, default: `default`) dan `data.fontSize` (`string ≤50`, in: `compact,default,spacious`, default: `default`) — lihat `DATA_MODEL.md`.

### `GET /cvs/{id}` → `200 { cv }` (lengkap dengan `data`)

### `PUT /cvs/{id}` → body sama seperti POST → `200 { cv }`

### `DELETE /cvs/{id}` → `204`

## AI

### `POST /ai/summary` (throttle: 5/menit/user, limiter bernama `ai`, nilai dari `AI_THROTTLE_PER_MINUTE`)

```json
{ "cv_id": 1, "job_description": "Dicari Senior Frontend Engineer..." }
```

```json
{ "summary": "Backend engineer dengan pengalaman 3 tahun ..." }
```

Server membaca data CV milik user dari DB — FE tidak mengirim ulang isi CV. Ringkasan fokus posisi dominan/terbaru dari `experiences` (bukan deskripsi proyek); `projects[].techStack` hanya konteks tambahan.

**ATS Keyword Tailoring (2026-09-18):** field opsional `job_description` (string, max 3000 char) memungkinkan penyelarasan ringkasan dengan target lowongan. Jika diisi, prompt AI menambahkan instruksi menyelaraskan kata kunci dan penekanan posisi dengan kebutuhan lowongan **tanpa mengarang fakta baru** — hanya menghubungkan pengalaman asli user dengan istilah lowongan. Di FE, textarea target lowongan tersedia di panel "Sesuaikan dengan Target Lowongan (ATS Tailoring)" pada step Ringkasan (collapsible, opsional). Prompt dipotong ke 1500 char di klien dan 3000 char di server.

Error AI gateway → `502 { "message": "AI service unavailable" }`.

### `POST /cvs/{id}/translate` (auth, throttle: 5/menit/user)

Menerjemahkan konten CV (`data`) dari bahasa sumber ke target tanpa menyimpan — FE yang memakai hasilnya untuk membuat CV baru (duplikat & terjemahkan).

```json
{ "target": "en" } // wajib opsional, in: id, en; default en
```

```json
{ "data": { ...CvData terjemahan } }
```

Field yang diterjemahkan: `summary`, `experiences[].position/description`, `education[].degree/achievements`, `organizations[].role/description`, `skills[].items`, `languages`, `certificates[].name`, `projects[].title/objective`. Nama, perusahaan, institusi, URL, dan angka dibiarkan verbatim (Google menerjemahkannya apa adanya). Label grup skill (`skills[].label`) **tidak** diterjemahkan — grup bawaan mengikuti `cv-labels.ts`, grup kustom dianggap konten user.

Implementasi: `TranslationService` memanggil endpoint gratis Google gtx (`translate.googleapis.com/translate_a/single?client=gtx`). Semua field digabung dengan delimiter `@@@` dalam satu request lalu dipecah kembali; jika Google merusak delimiter, fallback per-field. Service dipakai di `App\Services\TranslationService` — konten field per item, satu request per CV. Error layanan → `502 { "message": "Layanan terjemahan tidak tersedia" }`.

> ponytail: endpoint gtx tidak resmi, tanpa SLA — bisa berhenti/rate-limit. Upgrade path: Google Cloud Translation API atau proxy LibreTranslate.

## PDF

### `GET /cvs/{id}/pdf`

Butuh cookie Sanctum dan kepemilikan CV. Controller membangun HTML `print.html` dengan `window.__CV_DATA__`/`__CV_TEMPLATE__`/`__CV_LANGUAGE__`, lalu memberikannya langsung ke `Browsershot::html()`. `__CV_LANGUAGE__` dipakai oleh `cv-labels.ts` untuk merender judul section sesuai bahasa pilihan. Data tipografi (`fontFamily` & `fontSize`) yang ada di `__CV_DATA__` diterapkan langsung oleh `print-main.ts` ke komponen `CvPreview` dan menunggu `document.fonts.ready` sebelum Browsershot mengambil snapshot PDF agar font dan skala ukuran 100% konsisten dengan preview.

→ `200` binary `application/pdf`, header `Content-Disposition: attachment; filename="Nama_CV.pdf"`.

→ `422` JSON `{ "message": "Lengkapi dulu sebelum mengunduh.", "errors": { "data.personal.name": ["Nama wajib diisi."], ... } }` bila `title` atau `data.personal.{name,email,phone,address}` kosong, **atau** format email/telepon salah (`data.personal.email` bukan email valid, `data.personal.phone` bukan angka + simbol `+ - ( ) . spasi` dengan min 7 digit), **atau** ada entri berulang setengah jadi (`data.<section>.<i>.<field>`, mis. `data.certificates.0.issuer`). Guard ini cerminan `REQUIRED_FIELDS` + `INVALID_FORMATS` + `ENTRY_RULES` klien (`web/src/lib/cv-validation.ts`) dan `Cv::missingForPdf()` di server; jaga ketiganya tetap sinkron.

> **Catatan (2026-09-15):** selain kepemilikan, endpoint ini kini memvalidasi kelengkapan minimum + format + entri sebelum membuat PDF (guard server, "Opsi B"). Prinsipnya: apa yang tak bisa disimpan (`required`/`required_with`/format), tak bisa diunduh. Tombol `PDF` di Dashboard di-disable lewat `cv.is_complete`; gate klien di editor tetap ada untuk umpan balik instan.

→ `502` JSON `{ "message": "Gagal membuat PDF. Coba lagi sebentar lagi." }` bila render Chromium/Browsershot gagal (2026-10-02). Controller merender PDF di dalam `try/catch` **sebelum** mengirim header, jadi kegagalan render tidak lagi menjadi respons rusak/`ERR_INVALID_RESPONSE` — klien menerima JSON yang bisa ditampilkan sebagai pesan. Klien juga memakai `fetch` ber-`credentials: "include"` + timeout 60 dtk, bukan `window.open`.

**Field `is_complete` (2026-09-15):** `CvResource` menyertakan boolean `is_complete` (= `Cv::isComplete()`) di setiap item — termasuk list `GET /cvs` — agar UI bisa men-disable tombol unduh tanpa perlu memuat `data` penuh. Ringan (satu boolean per CV).

**Validasi format (semua endpoint tulis):** `data.personal.email` → `email`; `data.personal.phone` → `regex:/^[0-9+().\-\s]{7,30}$/`. Pesan Indonesia lewat `StoreCvRequest::messages()` (locale aplikasi `en`). Draft (`?draft=1`) melonggarkan `required*` → `nullable` tetapi **tetap** menegakkan format bila field terisi.

### `GET /cvs/{id}/print` (signed, shell debug internal)

→ `200` `text/html`. Route ini menerima `?expires=&signature=` melalui middleware `signed`, lalu me-return shell print dengan data CV ter-embed. Route dipertahankan untuk inspeksi internal, tetapi `PdfService` tidak memanggilnya. Jangan panggil langsung dari frontend.

## Contoh cURL

```bash
# login
curl -c jar.txt -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"secret"}'

# download pdf
curl -b jar.txt -o cv.pdf http://localhost:8000/api/v1/cvs/1/pdf
```
