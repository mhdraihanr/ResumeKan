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
        string template "modern|classic"
        string language "id|en"
        json data
        timestamps timestamps
    }
```

## Tabel `users`

Standar Laravel (`id`, `name`, `email` unique, `password`, `timestamps`). Tidak ada kolom tambahan di v1.

## Tabel `cvs`

| Kolom        | Tipe        | Aturan                                            |
| ------------ | ----------- | ------------------------------------------------- |
| `id`         | bigint PK   |                                                   |
| `user_id`    | FK → users  | `cascadeOnDelete()`                               |
| `title`      | string(100) | required                                          |
| `template`   | string(20)  | in: modern, classic — default `modern`            |
| `language`   | string(2)   | in: id, en — default `id`                         |
| `data`       | json        | struktur CvData di bawah; divalidasi Form Request |
| `timestamps` |             |                                                   |

Index: `user_id`. Tidak perlu index lain.

## Skema `data` (JSON)

```jsonc
{
  "personal": {
    "name": "string ≤100",
    "email": "email",
    "phone": "string ≤30",
    "address": "string ≤200",
    "linkedin": "string ≤500, opsional — dukung www. tanpa https:// (dinormalisasi ke https://)",
    "website": "string ≤500, opsional — dukung www. tanpa https:// (dinormalisasi ke https://)",
    "github": "string ≤500, opsional — dukung www./github.com tanpa https:// (dinormalisasi ke https://)",
  },
  "summary": "string ≤600",
  "experiences": [
    {
      // array, max 10 item
      "company": "string",
      "position": "string",
      "location": "string, opsional",
      "startDate": "YYYY-MM atau teks bebas",
      "endDate": "YYYY-MM | Present",
      "description": "string ≤1500, bullet dipisah newline",
    },
  ],
  "education": [
    {
      // array, max 5 item
      "institution": "string",
      "degree": "string",
      "major": "string, opsional",
      "year": "string",
      "gpa": "string ≤10, opsional — format 3.85 atau 3.85/4.00, tampil sebagai IPK: ...",
      "achievements": "string, opsional",
    },
  ],
  "skills": {
    "hard": "string ≤500, comma-separated",
    "soft": "string ≤300, comma-separated",
  },
  "languages": "string ≤200",
  "certificates": "string ≤1000, opsional",
  "projects": [
    {
      // array, max 5 item, opsional
      "title": "string ≤100 — nama proyek",
      "role": "string ≤100 — peran Anda di proyek",
      "objective": "string ≤500 — tujuan / apa yang diselesaikan",
      "techStack": "string ≤200 — comma-separated, mis. React, Go, PostgreSQL",
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
Backward compat: `projects` lama berupa `string` diterima dan dikonversi ke array 1 item saat validasi (lihat `StoreCvRequest::prepareForValidation`).

## Estimasi Ukuran

1 CV ≈ 2–5 KB → kapasitas Neon free (0.5 GB) ≫ kebutuhan bertahun-tahun.
