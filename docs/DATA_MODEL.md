# Data Model — ResumeKan

> SQLite lokal, Postgres (Neon) produksi. Migration Laravel agnostik driver.

## ERD

```mermaid
erDiagram
    users ||--o{ cvs : owns

    users {
        id id
        string name
        string email UNIQUE
        string password
        timestamps timestamps
    }

    cvs {
        id id
        id user_id FK
        string title
        string template "modern|classic|neon"
        string language "id|en"
        json data
        timestamps timestamps
    }
```

## Tabel `users`

Standar Laravel (`id`, `name`, `email` unique, `password`, `timestamps`). Tidak ada kolom tambahan di v1.

## Tabel `cvs`

| Kolom        | Tipe        | Aturan                                                                                                                                                                                                                                                                                                    |
| ------------ | ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`         | bigint PK   |                                                                                                                                                                                                                                                                                                           |
| `user_id`    | FK → users  | `cascadeOnDelete()`                                                                                                                                                                                                                                                                                       |
| `title`      | string(100) | required                                                                                                                                                                                                                                                                                                  |
| `template`   | string(20)  | in: modern, classic, neon — default `modern` (1 template = 1 file: `CvModern.vue`/`CvClassic.vue`/`CvNeon.vue`)                                                                                                                                                                                           |
| `language`   | string(2)   | in: id, en — default `id`. Menentukan bahasa judul/label section dokumen via `cv-labels.ts` (F7); konten user tidak diterjemahkan (heading ID/EN saja). Terjemahan konten dilakukan di fitur duplikat & terjemahkan (fase-7 7b) dan disimpan sebagai CV `language: "en"` baru — tidak mengubah field ini. |
| `data`       | json        | struktur CvData di bawah; divalidasi Form Request                                                                                                                                                                                                                                                         |
| `timestamps` |             |                                                                                                                                                                                                                                                                                                           |

Index: `user_id`. Tidak perlu index lain.

## Skema `data` (JSON)

```jsonc
{
  "fontFamily": "string ≤50, opsional — in: default, inter, source-sans, lora, merriweather (default: 'default')",
  "fontSize": "string ≤50, opsional — in: compact, default, spacious (default: 'default')",
  "personal": {
    "name": "string ≤100",
    "email": "email",
    "phone": "string ≤30",
    "address": "string ≤200",
    "linkedin": "string ≤500, opsional — dukung www. tanpa https:// (dinormalisasi ke https://)",
    "website": "string ≤500, opsional — dukung www. tanpa https:// (dinormalisasi ke https://)",
    "github": "string ≤500, opsional — dukung www./github.com tanpa https:// (dinormalisasi ke https://)",
    "photo": "string ≤2000, opsional — URL Cloudinary (upload via `POST /upload-signature`), hanya dipakai template neon",
  },
  "summary": "string ≤600",
  "experiences": [
    {
      // array, max 10 item
      "company": "string",
      "position": "string",
      "location": "string, opsional",
      "employmentType": "string, opsional — Full-time | Part-time | Internship | Contract | Freelance",
      "startDate": "YYYY-MM atau teks bebas",
      "endDate": "YYYY-MM | Present",
      "description": "string ≤1500, bullet dipisah newline",
    },
  ],
  "education": [
    {
      // array, max 5 item
      "institution": "string",
      "degree": "string — gelar & jurusan digabung, mis. 'S1 Teknik Informatika' / 'Bachelor of Science in Computer Science'",
      "location": "string, opsional — kota/lokasi kampus",
      "year": "string",
      "gpa": "string ≤10, opsional — format 3.85 atau 3.85/4.00, tampil sebagai IPK: ...",
      "achievements": "string ≤1000, opsional — prestasi/deskripsi/ekstrakurikuler, bullet dipisah newline",
    },
  ],
  "skills": [
    {
      // array, max 5 item — grup keahlian
      "label": "string ≤40 — nama grup, mis. 'Hard skills' / 'Library & Frameworks'",
      "items": "string ≤500, opsional — comma-separated, mis. 'Vue 3, React, Tailwind'",
    },
  ],
  "languages": "string ≤200",
  "certificates": [
    {
      // array, max 5 item, opsional — section sendiri, heading ATS Sertifikasi/Certifications
      "name": "string ≤100 — nama sertifikat",
      "issuer": "string ≤100 — penerbit, tampil 'by Penerbit' bold",
      "year": "string ≤10 — tahun terbit, mis. 2024",
      "credentialId": "string ≤100, opsional — ID kredensial verifikasi",
    },
  ],
  "projects": [
    {
      // array, max 8 item, opsional
      "title": "string ≤100 — nama proyek",
      "role": "string ≤100 — peran Anda di proyek",
      "objective": "string ≤500 — tujuan / apa yang diselesaikan",
      "techStack": "string ≤200 — comma-separated, mis. React, Go, PostgreSQL",
      "link": "string ≤500, opsional — URL proyek, dukung www. tanpa https:// (dinormalisasi ke https://), tampil ikon external-link di sebelah title",
    },
  ],
  "organizations": [
    {
      // array, max 5 item, opsional — heading ATS Organisasi
      "organization": "string ≤100 — nama organisasi",
      "role": "string ≤100 — jabatan/peran",
      "period": "string ≤30 — mis. 2022 — 2024",
      "description": "string ≤800, opsional — bullet dipisah newline",
    },
  ],
}
```

Aturan validasi global: setiap array maksimal sesuai catatan; total payload JSON ≤ 50 KB.
Backward compat: `projects`/`certificates` lama berupa `string` diterima dan dikonversi ke array 1 item saat validasi (lihat `StoreCvRequest::prepareForValidation`). Field tipografi `fontFamily` dan `fontSize` bersifat opsional dan otomatis diberi nilai default `"default"` jika belum ada di data lama. Frontend juga menormalisasi saat load via `normalizeCvData()` di `types/cv.ts` (dipakai `CvFormView`, `CvPreview`, `print-main`) agar data lama tidak render kosong.

**Backward compat `skills` (2026-09-18):** `skills` dulu objek tetap `{ hard, soft }`, kini array grup `[{ label, items }]` supaya pengguna bisa menambah grup kustom (mis. "Library & Frameworks"). Data lama dikonversi di tiga tempat — `StoreCvRequest::prepareForValidation()` (server, saat simpan), `normalizeSkills()` di `types/cv.ts` (klien, saat load), dan Artisan command `cv:normalize-skills` (`--dry-run` tersedia, idempotent) untuk merapikan baris lama di database sekaligus — dengan aturan: `hard` → `{ label: "Hard skills", items }`, `soft` → `{ label: "Soft skills", items }`, dan grup yang `items`-nya kosong dibuang. Dua grup bawaan selalu menempati dua posisi pertama dan **tidak bisa dihapus/di-rename** dari form; labelnya diterjemahkan per bahasa via `skillLabel()` di `cv-labels.ts` (label kustom dibiarkan apa adanya karena konten user tidak diterjemahkan). Klien lama yang masih mengirim objek `{ hard, soft }` tetap diterima.

**Pruning grup skill:** sisi klien (`lib/cv-validation.ts`) membuang grup kustom yang label **dan** items-nya kosong ("klik Tambah grup lalu batal" bukan error), sejalan dengan aturan pruning entri lain. Grup kustom yang berisi items tapi tanpa label memunculkan error inline (`Nama grup keahlian wajib diisi`).

Entri berulang (`experiences`, `education`, `organizations`, `certificates`, `projects`) punya field wajib bersyarat (`required_with`): begitu array berisi entri, field wajibnya harus terisi. Sisi klien menyelaraskan diri lewat dua aturan di `lib/cv-validation.ts`: (1) entri yang **seluruh** field wajibnya kosong dibuang otomatis saat submit — "klik + Tambah lalu batal" bukan error; (2) entri yang **terisi sebagian** dipertahankan dan memunculkan error inline. Karena itu backend tidak perlu aturan tambahan; pruning murni keputusan UX klien dan tidak mengubah kontrak API.

**Mode draft (`?draft=1`, 2026-09-15):** saat flag draft aktif, `StoreCvRequest` melonggarkan semua `required`/`required_with` menjadi `nullable` (tipe/`max`/`in` tetap dicek), sehingga progres setengah jadi bisa dipersist. Kolom `title` (NOT NULL) diisi placeholder `"CV Tanpa Judul"` oleh `CvController::payload()` bila kosong. Submit final tanpa flag tetap memakai aturan ketat. Struktur `CvData` **tidak berubah** — hanya kewajiban isinya yang berbeda per mode.

## Estimasi Ukuran

1 CV ≈ 2–5 KB → kapasitas Neon free (0.5 GB) ≫ kebutuhan bertahun-tahun.
