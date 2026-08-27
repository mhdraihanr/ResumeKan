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

| File                               | Isi                                                                                       |
| ---------------------------------- | ----------------------------------------------------------------------------------------- |
| `web/src/types/cv.ts`              | Tipe `Cv`, `CvData`, `emptyCvData()`                                                      |
| `web/src/api/cv.ts`                | Wrapper fetch + CSRF                                                                      |
| `web/src/stores/cv.ts`             | `fetchList/fetchOne/create/update/remove`                                                 |
| `web/src/components/cv/CvForm.vue` | Form lengkap: personal, summary, experiences (max 10), education (max 5), skills, lainnya |
| `web/src/views/CvFormView.vue`     | Halaman buat/edit — load data saat edit                                                   |
| `web/src/views/DashboardView.vue`  | Grid card CV + empty state + hapus (confirm)                                              |

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

## Definisi Selesai

- [x] Buat, lihat daftar, edit, hapus CV dari dashboard.
- [x] CV ke-11 ditolak `422`.
- [x] User tidak bisa akses CV milik user lain (`403`).

← [Fase 1](phase-1-auth.md) · Lanjut ke [Fase 3](phase-3-preview-template.md)
