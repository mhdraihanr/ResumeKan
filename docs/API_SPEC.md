# API Spec — ResumeKan

> Base URL: `/api/v1`. Auth: cookie Sanctum (`/api/v1/*` stateful).
> Response JSON, kecuali unduhan PDF dan shell print internal. Error JSON memakai `{ "message": string, "errors"?: { field: string[] } }`.

## Konvensi

- `401` belum login · `403` bukan pemilik resource · `404` tidak ada · `422` validasi · `429` rate limit.
- Semua route, selain auth dan shell print internal bertanda tangan, butuh login.

## Auth

| Method | Path        | Body                                           | Response                |
| ------ | ----------- | ---------------------------------------------- | ----------------------- |
| POST   | `/register` | `name, email, password, password_confirmation` | `201 { user }` + cookie |
| POST   | `/login`    | `email, password`                              | `{ user }` + cookie     |
| POST   | `/logout`   | —                                              | `204`                   |
| GET    | `/user`     | —                                              | `{ user }`              |

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

> `data.projects` terstruktur: array objek `{ title, role, objective, techStack, link? }` (max 5, `link` opsional ≤500 dinormalisasi `https://`). `data.certificates` terstruktur: array objek `{ name, issuer, year, credentialId? }` (max 5, section sendiri). Nilai lama `string` masih diterima untuk keduanya (backward compat, dikonversi ke 1 item). `data.education[].gpa` opsional `≤10`, `data.education[].location` opsional, `data.education[].degree` = gelar & jurusan digabung (field `major` dihapus), `data.education[].achievements` opsional `≤1000` (bullet newline), `data.organizations` array max 5, `data.experiences[].employmentType` opsional `in: Full-time,Part-time,Internship,Contract,Freelance` — lihat `DATA_MODEL.md`.

### `GET /cvs/{id}` → `200 { cv }` (lengkap dengan `data`)

### `PUT /cvs/{id}` → body sama seperti POST → `200 { cv }`

### `DELETE /cvs/{id}` → `204`

## AI

### `POST /ai/summary` (throttle: 5/menit/user)

```json
{ "cv_id": 1 }
```

```json
{ "summary": "Backend engineer dengan pengalaman 3 tahun ..." }
```

Server membaca data CV milik user dari DB — FE tidak mengirim ulang isi CV. Ringkasan fokus posisi dominan/terbaru dari `experiences` (bukan deskripsi proyek); `projects[].techStack` hanya konteks tambahan.
Error AI gateway → `502 { "message": "AI service unavailable" }`.

### `POST /cvs/{id}/translate` (auth, throttle: 5/menit/user)

Menerjemahkan konten CV (`data`) dari bahasa sumber ke target tanpa menyimpan — FE yang memakai hasilnya untuk membuat CV baru (duplikat & terjemahkan).

```json
{ "target": "en" } // wajib opsional, in: id, en; default en
```

```json
{ "data": { ...CvData terjemahan } }
```

Field yang diterjemahkan: `summary`, `experiences[].position/description`, `education[].degree/achievements`, `organizations[].role/description`, `skills.hard/soft`, `languages`, `certificates[].name`, `projects[].title/objective`. Nama, perusahaan, institusi, URL, dan angka dibiarkan verbatim (Google menerjemahkannya apa adanya).

Implementasi: `TranslationService` memanggil endpoint gratis Google gtx (`translate.googleapis.com/translate_a/single?client=gtx`). Semua field digabung dengan delimiter `@@@` dalam satu request lalu dipecah kembali; jika Google merusak delimiter, fallback per-field. Service dipakai di `App\Services\TranslationService` — konten field per item, satu request per CV. Error layanan → `502 { "message": "Layanan terjemahan tidak tersedia" }`.

> ponytail: endpoint gtx tidak resmi, tanpa SLA — bisa berhenti/rate-limit. Upgrade path: Google Cloud Translation API atau proxy LibreTranslate.

## PDF

### `GET /cvs/{id}/pdf`

Butuh cookie Sanctum dan kepemilikan CV. Controller membangun HTML `print.html` dengan `window.__CV_DATA__`/`__CV_TEMPLATE__`/`__CV_LANGUAGE__`, lalu memberikannya langsung ke `Browsershot::html()`. `__CV_LANGUAGE__` dipakai oleh `cv-labels.ts` untuk merender judul section sesuai bahasa pilihan.

→ `200` binary `application/pdf`, header `Content-Disposition: attachment; filename="Nama_CV.pdf"`.

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
