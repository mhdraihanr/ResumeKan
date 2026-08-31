# Fase 2 — CRUD CV ✅

> Status: **Selesai** (2026-08-27) · Estimasi: 1 hari · Prasyarat: [Fase 1](phase-1-auth.md) ✅

## Yang Dikerjakan

- [x] Form Request `StoreCvRequest` — validasi skema JSON `data` sesuai [Data Model](../DATA_MODEL.md) + batas payload
- [x] `CvController` + `CvResource` — CRUD + batas 10 CV (`422`) + `403` jika bukan pemilik
- [x] Dashboard daftar CV + form buat/edit (`CvForm.vue`, `CvFormView.vue`)
- [x] Store `cv` + API wrapper `api/cv.ts` + route `/cvs/new`, `/cvs/:id/edit`

## Hasil Implementasi

### Backend

| Endpoint           | Implementasi                                        |
| ------------------ | --------------------------------------------------- |
| `GET /cvs`         | `CvController@index` — daftar tanpa `data` (ringan) |
| `POST /cvs`        | `CvController@store` — validasi + cek batas 10 CV   |
| `GET /cvs/{id}`    | `CvController@show` — `403` jika bukan pemilik      |
| `PUT /cvs/{id}`    | `CvController@update` — validasi + `403`            |
| `DELETE /cvs/{id}` | `CvController@destroy` — `204`                      |

### Frontend

| File                               | Isi                                                                                                                                                                                                                                                               |
| ---------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `web/src/types/cv.ts`              | Tipe `Cv`, `CvData`, `emptyCvData()`                                                                                                                                                                                                                              |
| `web/src/api/cv.ts`                | Wrapper fetch + CSRF                                                                                                                                                                                                                                              |
| `web/src/stores/cv.ts`             | `fetchList/fetchOne/create/update/remove`                                                                                                                                                                                                                         |
| `web/src/components/cv/CvForm.vue` | Form lengkap: personal, summary, experiences (max 10), education (max 5), skills, lainnya. Stepper tabs 9 langkah (2026-08-30: Info→Pribadi→Ringkasan→Pengalaman→Pendidikan→Organisasi→Keahlian→Proyek→Lainnya, `v-show` per step, Prev/Next, progress indicator) |
| `web/src/views/CvFormView.vue`     | Halaman buat/edit — load data saat edit                                                                                                                                                                                                                           |
| `web/src/views/DashboardView.vue`  | Grid card CV + empty state + hapus (confirm)                                                                                                                                                                                                                      |

## Hasil Verifikasi

> Alur uji: [TESTING.md](../TESTING.md)

| Uji                                                      | Hasil                                    |
| -------------------------------------------------------- | ---------------------------------------- |
| curl: create → list → show → update → delete             | `201` → `200` → `200` → `200` → `204` ✅ |
| Browser: login → dashboard empty → buat CV → card muncul | ✅                                       |
| Browser: edit CV → data terisi → kembali                 | ✅                                       |
| Browser: hapus CV → confirm → kembali empty              | ✅                                       |
| `vue-tsc --build`                                        | 0 error                                  |
| `php artisan test`                                       | hijau                                    |

## Pelajaran Penting

1. `CvResource` membedakan response: `GET /cvs` tanpa `data` (ringan), `GET /cvs/{id}` + POST/PUT dengan `data` lengkap.
2. Validasi `data` di Form Request — bukan di DB constraint (ADR-3: JSON tunggal).
3. **Enhancement 2026-08-27** — `projects` dari `string` → array terstruktur `{ title, role, objective, techStack }` (max 5). Backward compat: string lama dikonversi ke 1 item di `prepareForValidation`. Experiences tetap `string` (1 baris = 1 bullet ATS) — hanya UI yang ditambah helper.

## Enhancement — Projects Terstruktur (2026-08-27)

> Iterasi setelah Fase 2 selesai. Tidak mengubah migrasi DB (kolom `data` JSON tetap).

| Hal             | Detail                                                                                              |
| --------------- | --------------------------------------------------------------------------------------------------- |
| Skema baru      | `projects: { title, role, objective, techStack }[]` max 5 — lihat [DATA_MODEL.md](../DATA_MODEL.md) |
| Validasi        | `StoreCvRequest` — rules per field + konversi string lama                                           |
| UI              | `CvForm.vue` — card list per proyek (mirip experiences), grid `sm:grid-cols-2` untuk title/role     |
| Backward compat | `projects: "string lama"` → `[{ title: string, role: "—", objective: "", techStack: "" }]`          |

## Enhancement — IPK & Organisasi (2026-08-28)

> Brainstorm Exa ATS: IPK di dalam `education[]`, Organisasi sebagai section terpisah (heading ATS standar).

| Hal        | Detail                                                                                                                                                                                                                                                                                                                                                                                                                        |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| IPK (GPA)  | `education[].gpa?: string ≤10` — format `3.85` atau `3.85/4.00`, tampil `IPK: 3.85/4.00` di bawah institusi. ATS: di dalam Education, label jelas, hanya isi jika ≥3.50 & fresh graduate. Validasi `nullable\|string\|max:10`. Preview `text-[9pt] text-slate-500`. Kosong → tidak render.                                                                                                                                    |
| Organisasi | `organizations?: { organization, role, period, description? }[]` max 5 — heading ATS `Organisasi`/`Organizations` terpisah (bukan di `education.achievements`). Form section baru setelah Pendidikan, card mirip experiences. Preview section dengan `border-b-[1.5px]` seragam, `role — organization` + `period` kanan, bullets newline. Kosong → section hilang. Backward compat: field opsional, payload lama tetap valid. |

## Enhancement — UI Skills (2026-08-30)

> Analisis Exa ATS: comma-separated sudah parseable; sweet spot 12–20 skill, urut sesuai JD, spesifik > umum.

| Hal         | Detail                                                                                                                                      |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| Input       | `skills.hard`/`skills.soft` dari `<input>` single-line → textarea auto-expand (pola `.auto-expand` yang sama) — isi panjang terlihat semua. |
| Placeholder | Contoh lebih panjang (8 item) + `placeholder:text-slate-400` (konsisten input lain, WCAG 3.3.2).                                            |
| Hint ATS    | Hard: urut sesuai JD, spesifik ("PostgreSQL" bukan "database"), ideal 12–20. Soft: hanya yang disebut JD, sisanya dibuktikan di bullet.     |

## Definisi Selesai

- [x] Buat, lihat daftar, edit, hapus CV dari dashboard.
- [x] CV ke-11 ditolak `422`.
- [x] User tidak bisa akses CV milik user lain (`403`).

← [Fase 1](phase-1-auth.md) · Lanjut ke [Fase 3](phase-3-preview-template.md)
