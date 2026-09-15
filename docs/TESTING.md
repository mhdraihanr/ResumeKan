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
  -d '{"email":"test@example.com","password":"password123"}'
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
POST   /api/v1/cvs { projects: [{ title, role, link: "github.com/x" }] } → 201  # link dinormalisasi ke https://
# preview: title tetap plain text (ATS), ikon external-link muncul hanya jika link ada
```

**Preview (Fase 3):** ubah field form → preview update tanpa lag; switch `modern` ↔ `classic` ↔ `neon` → header/heading/accent/layout berubah; cek di mobile (stack/tab). Preview editor kini paged (2026-09-08): CV panjang tampil sebagai beberapa lembar A4 (kertas putih ber-margin, di-scale muat panel) — cek tidak ada judul section/entry terpotong di tepi bawah lembar, dan jumlah lembar sesuai panjang konten (mis. CV 2 halaman → 2 lembar). Cek juga (2026-09-09): saat panel preview lebih sempit dari A4 (zoom-out), tidak ada ruang kosong besar di kanan/bawah kartu — lembar menempati area kartu secara rapat; sticky bar atas selebar kolom form+preview (tidak melebar penuh layar).

**Simpan Draft (Fase 3):** di halaman `/cvs/new` isi minimal (judul + data pribadi) → klik `Simpan Draft` → toast `Draft tersimpan` muncul, URL tetap `/cvs/new`, heading berubah jadi "Edit CV", tombol `Download PDF` muncul; refresh halaman → data masih ada. Di halaman edit: ubah field → `Simpan Draft` → toast muncul tanpa keluar halaman; cek DB `updated_at` berubah.

**Validasi submit (2026-09-15):** `Simpan CV` tidak lagi memakai bubble HTML native maupun pesan 422 mentah. Yang diuji:

1. Kosongkan Judul CV, klik `Simpan CV` dari step mana pun (mis. dari step 10) → pindah ke step 1 "Info", fokus ke heading `Info CV`, muncul banner `role="alert"` ("Ada N isian yang perlu diperbaiki...") + teks inline `Judul CV wajib diisi.` di bawah field, input `aria-invalid="true"`, stepper step 1 bertanda `!` merah.
2. Sebelum percobaan simpan pertama, stepper **tidak** menandai field kosong dengan `!` (jangan menghukum sebelum pengguna diberi tahu).
3. Isi Judul CV → error inline hilang otomatis tanpa submit ulang (re-validasi live).
4. Kosongkan field Pribadi (Nama/Email/Telepon/Alamat) → submit → pindah ke step 2 "Pribadi" dengan banner menyebut jumlah yang kurang.
5. **Entri kosong:** klik `+ Tambah` di Sertifikat/Proyek/Pengalaman lalu tidak diisi → `Simpan CV` → entri dibuang otomatis, **tidak ada error**, muncul catatan halus `1 entri kosong diabaikan`; cek DB `data.certificates = []`.
6. **Entri setengah terisi:** isi hanya Nama sertifikat → submit → entri **dipertahankan**, error inline di Penerbit + Tahun terbit (`Penerbit (Sertifikat #1) wajib diisi.`), pindah ke step 9.
7. Verifikasi aksesibilitas: pesan error terbaca sebagai bagian deskripsi field (nama aksesibel input menyertakan teks error), dan toast draft punya `role="status"`/`aria-live="polite"`.
8. Kontras teks error: light `#c10007` di atas putih 6.42:1; dark `red-300` (`#ffa2a2`) di atas kartu gelap `#3f3f46` 5.44:1 — keduanya lolos WCAG AA. Error tidak pernah ditandai warna saja (selalu ada teks + `!` di stepper). Catatan: warna error dark hanya berlaku bila blok `.cv-form` di `CvForm.vue` berada di dalam `@layer components` — CSS unlayered selalu menimpa utility Tailwind ber-layer tanpa peduli specificity (bug 2026-09-15: `.dark .cv-form p` unlayered menimpa `dark:text-red-300` sehingga semua pesan error jadi abu).

**Simpan draft parsial (`?draft=1`, 2026-09-15):** tombol `Simpan Draft` menyimpan progres setengah jadi dan **tidak** menampilkan pesan 422 mentah.

1. Isi form kosong (judul kosong), klik `Simpan Draft` → `POST /cvs?draft=1` → `201`, toast `Draft tersimpan`, DB `title = "CV Tanpa Judul"` (placeholder). Sebelumnya: `422` + toast mentah `The data.title field is required...`.
2. Klik `+ Tambah` Sertifikat lalu biarkan kosong, klik `Simpan Draft` → entri kosong di-prune di klien (`pruneEntries`) → `201`, DB `data.certificates = []`. Sebelumnya: `422 The data.certificates.0.issuer field is required when data.certificates is present. (and N more errors)`.
3. Bila server tetap menolak (`422`), `draftSave` memetakan `errors` ke error inline via `applyServerErrors` + toast ringkas `Ada isian yang perlu diperbaiki. Cek penanda merah.` — bukan `message` mentah.
4. Kegagalan operasional (network/5xx) → toast `Gagal menyimpan draft. Coba lagi.`
5. Submit final **tanpa** `?draft=1` tetap ketat: `title`, `data.personal.*`, dan `required_with` entri masih wajib (verifikasi ruleset strict vs draft via unit test).

**AI (Fase 4):** `POST /api/v1/ai/summary` → `200`; request ke-6 dalam 1 menit → `429`.

**PDF (Fase 5):** dengan session aktif, `GET /api/v1/cvs/{id}/pdf` → `200 application/pdf`; cek signature awal `%PDF-`, nama file di header `Content-Disposition`, dan ukuran file lebih dari satu halaman kosong. Dari halaman edit, klik **Download PDF** dan pastikan file bernama `{nama}_CV.pdf` terunduh serta kontennya sama dengan preview. Bila PDF kosong, cek bahwa `PdfService` memakai `Browsershot::html()` dan argumen Chromium untuk module dari shell `file://`, bukan request URL print balik ke API. Cek paginasi (2026-09-08): PDF multi-halaman tidak memotong judul section/entry di tengah (break-inside avoid) dan titik pecah halaman sama dengan preview editor.

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
