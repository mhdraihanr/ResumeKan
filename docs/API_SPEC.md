# API Spec — ResumeKan

> Base URL: `/api/v1`. Auth: cookie Sanctum (`/api/v1/*` stateful).
> Semua response JSON. Error format: `{ "message": string, "errors"?: { field: string[] } }`.

## Konvensi

- `401` belum login · `403` bukan pemilik resource · `404` tidak ada · `422` validasi · `429` rate limit.
- Semua route (kecuali auth) butuh login.

## Auth

| Method | Path        | Body                                           | Response                |
| ------ | ----------- | ---------------------------------------------- | ----------------------- |
| POST   | `/register` | `name, email, password, password_confirmation` | `201 { user }` + cookie |
| POST   | `/login`    | `email, password`                              | `{ user }` + cookie     |
| POST   | `/logout`   | —                                              | `204`                   |
| GET    | `/user`     | —                                              | `{ user }`              |

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
  "template": "modern",            // modern | classic
  "language": "id",                // id | en
  "data": { ...CvData }            // lihat DATA_MODEL.md
}
```

→ `201 { cv }`. Gagal jika user sudah punya 10 CV → `422`.

> `data.projects` terstruktur: array objek `{ title, role, objective, techStack }` (max 5). Nilai lama `string` masih diterima (backward compat, dikonversi ke 1 item). `data.education[].gpa` opsional `≤10`, `data.organizations` array max 5 — lihat `DATA_MODEL.md`.

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

Server membaca data CV milik user dari DB — FE tidak mengirim ulang isi CV.
Error Gemini → `502 { "message": "AI service unavailable" }`.

## PDF

### `GET /cvs/{id}/pdf`

→ `200` binary `application/pdf`, header `Content-Disposition: attachment; filename="Nama_CV.pdf"`.
Timeout render 30s → `504`.

## Contoh cURL

```bash
# login
curl -c jar.txt -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"a@b.c","password":"secret"}'

# download pdf
curl -b jar.txt -o cv.pdf http://localhost:8000/api/v1/cvs/1/pdf
```
