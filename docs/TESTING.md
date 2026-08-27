# Testing — ResumeKan

> Alur uji standar. Setiap fase punya skenario di bawah; jalankan sebelum centang fase di [Roadmap](ROADMAP.md).

## 1. API (curl)

### Aturan wajib (punya kesalahan yang sudah pernah terjadi)

| Kesalahan                   | Gejala                                   | Solusi                                                                                                                |
| --------------------------- | ---------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Path CSRF salah             | `404 route not found`                    | CSRF ada di `/sanctum/csrf-cookie` (**tanpa** prefix `/api`)                                                          |
| Token XSRF basi             | `419 CSRF token mismatch`                | Ambil ulang `XSRF-TOKEN` dari jar **sebelum setiap POST** (token berubah tiap login/logout karena session regenerate) |
| Tanpa header Origin/Referer | `401 Unauthenticated` padahal cookie ada | Sanctum stateful check butuh `Origin: http://localhost:5173`                                                          |

### Resep: satu request POST ter-autentikasi

```bash
# 0. Siapkan jar baru tiap sesi uji
rm -f /tmp/jar.txt
BASE=http://localhost:5173   # lewat proxy Vite; ganti :8000 untuk direct

# 1. Ambil CSRF cookie
curl -s -c /tmp/jar.txt $BASE/sanctum/csrf-cookie -o /dev/null

# 2. Ekstrak token FRESH dari jar (ulangi langkah ini sebelum SETIAP POST)
TOKEN=$(grep XSRF-TOKEN /tmp/jar.txt | awk '{print $7}' \
  | python -c "import sys,urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))")

# 3. POST dengan token + Origin
curl -s -b /tmp/jar.txt -c /tmp/jar.txt -X POST $BASE/api/v1/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" -H "Referer: http://localhost:5173/" \
  -H "X-XSRF-TOKEN: $TOKEN" \
  -d '{"email":"a@b.c","password":"password123"}'
```

### Skenario per fitur

**Auth (Fase 1)** — urutan wajib, cek status code tiap langkah:

```bash
register → 201   # pakai resep di atas, body register
GET    /api/v1/user        → 200  # session aktif
POST   /api/v1/logout      → 204  # ambil TOKEN fresh dulu!
GET    /api/v1/user        → 401  # session mati
POST   /api/v1/login       → 200  # login ulang
```

**CRUD CV (Fase 2)** — setelah login:

```bash
POST   /api/v1/cvs         → 201
GET    /api/v1/cvs         → 200  # daftar tanpa field data
GET    /api/v1/cvs/1       → 200
PUT    /api/v1/cvs/1       → 200
DELETE /api/v1/cvs/1       → 204
POST   /api/v1/cvs ke-11   → 422  # batas 10 CV
# validasi projects terstruktur:
POST   /api/v1/cvs { projects: [{ title:"", role:"" }] } → 422  # title/role required
POST   /api/v1/cvs { projects: string lama }             → 201  # backward compat
```

**AI (Fase 4):** `POST /api/v1/ai/summary` → `200`; request ke-6 dalam 1 menit → `429`.

**PDF (Fase 5):** `GET /api/v1/cvs/{id}/pdf` → binary PDF; cek nama file di header `Content-Disposition`.

## 2. SPA (browser)

Gunakan browser bawaan Copilot (bukan devtools eksternal). Pola uji per fase:

1. Buka URL halaman target (mis. `http://localhost:5173/register`).
2. Isi form → submit → snapshot halaman.
3. **Ekspektasi**: redirect & konten sesuai (mis. register → `/dashboard` + nama user tampil).
4. Uji negatif: logout → akses langsung `/dashboard` → harus dialihkan ke `/login`.
5. Cek console error hanya untuk error fungsional; `ERR_ABORTED` pada `/sanctum/csrf-cookie` adalah normal (fetch duplikat dibatalkan router).

## 3. Otomatis

```bash
cd api && php artisan test          # backend (PHPUnit)
cd web && pnpm run type-check       # frontend (vue-tsc)
```

Keduanya wajib hijau sebelum fase dinyatakan selesai. Test fitur baru ditulis saat fase bersangkutan dikerjakan.
