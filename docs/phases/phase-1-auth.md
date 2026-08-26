# Fase 1 — Auth ✅

> Status: **Selesai** (2026-08-26) · Estimasi: ½ hari · Prasyarat: [Fase 0](phase-0-scaffold.md) ✅

## Yang Dikerjakan

- [x] Endpoint register/login/logout/user sesuai [API Spec](../API_SPEC.md#auth)
- [x] Pinia store auth + guard route `/dashboard`

## Hasil Implementasi

### Backend

| Endpoint         | Implementasi                                                                     |
| ---------------- | -------------------------------------------------------------------------------- |
| `POST /register` | `AuthController@register` — validasi + `Auth::login()` + regenerate session      |
| `POST /login`    | `AuthController@login` — `Auth::attempt()` + regenerate session                  |
| `POST /logout`   | `AuthController@logout` — logout + invalidate session + regenerate token → `204` |
| `GET /user`      | `AuthController@user` — di belakang `auth:sanctum`                               |

Route closure dari Fase 0 sudah dipindah ke `api/app/Http/Controllers/AuthController.php`.

### Frontend

| File                              | Isi                                                                   |
| --------------------------------- | --------------------------------------------------------------------- |
| `web/src/stores/auth.ts`          | + `logout()`, dipakai guard & tombol logout                           |
| `web/src/views/LoginView.vue`     | Form login, redirect ke `/dashboard` saat sukses                      |
| `web/src/views/RegisterView.vue`  | Form register (nama/email/password/konfirmasi)                        |
| `web/src/views/DashboardView.vue` | Sapaan user + tombol logout (redirect `/login`) + placeholder CRUD CV |
| `web/src/router/index.ts`         | Route `/login`, `/register`, `/dashboard` + `beforeEach` guard        |

Guard `/dashboard`: jika belum ada user di store → `fetchUser()` cek session → tetap tidak ada → redirect `/login`.

## Hasil Verifikasi

> Alur uji lengkap: [TESTING.md](../TESTING.md)

| Uji                                   | Hasil                                                     |
| ------------------------------------- | --------------------------------------------------------- |
| Register dari browser SPA             | `201` → redirect `/dashboard`, nama user tampil           |
| Logout dari browser SPA               | Session mati; akses `/dashboard` lagi → redirect `/login` |
| Login ulang dari browser SPA          | `200` → kembali ke `/dashboard`                           |
| curl: register → user → logout → user | `201` → `200` → `204` → `401` ✅                          |
| `vue-tsc --build`                     | 0 error                                                   |
| `php artisan test`                    | hijau                                                     |

## Pelajaran Penting

1. **CSRF token berubah setelah login/logout** (`session()->regenerate()`). Browser otomatis baca ulang cookie `XSRF-TOKEN`; tapi kalau test via curl, ambil token fresh dari jar sebelum request POST berikutnya — token lama → `419`.
2. **Logout wajib invalidate + regenerateToken**, bukan cuma `Auth::logout()`, agar session lama tidak bisa dipakai ulang.
3. Request `ERR_ABORTED` pada `/sanctum/csrf-cookie` di log browser adalah normal (preflight/duplikat fetch yang dibatalkan) — bukan error fungsional.
4. **Logout harus navigasi eksplisit** (`router.push("/login")` setelah `auth.logout()`). Router guard hanya jalan saat navigasi, bukan saat state berubah — tanpa push, halaman diam di `/dashboard` dan nama user kosong ("Halo, 👋") sampai refresh. Pola yang sama berlaku untuk semua perubahan auth state.

## Definisi Selesai

- [x] Register → login → logout → login ulang dari SPA tanpa refresh manual.
- [x] Akses `/dashboard` tanpa login dialihkan ke halaman login.

← [Fase 0](phase-0-scaffold.md) · Lanjut ke [Fase 2](phase-2-crud-cv.md)
