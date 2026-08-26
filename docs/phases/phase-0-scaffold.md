# Fase 0 — Scaffold ✅

> Status: **Selesai** (2026-08-25) · Estimasi: ½ hari · Kriteria terverifikasi dari browser.

## Yang Dikerjakan

- [x] `composer create-project laravel/laravel api` (Laravel 12, PHP 8.4 via Herd Lite)
- [x] Sanctum (`laravel/sanctum` ^4.3) + `php artisan install:api`
- [x] Migrasi `users` (bawaan) & `cvs` — lihat [Data Model](../DATA_MODEL.md)
- [x] `pnpm create vue@latest web` (TypeScript + Router + Pinia)
- [x] Tailwind CSS v4 via `@tailwindcss/vite`
- [x] Proxy dev Vite → `localhost:8000`, CORS & stateful domain

## Hasil Verifikasi

> Alur uji lengkap: [TESTING.md](../TESTING.md)

| Uji                                      | Hasil                           |
| ---------------------------------------- | ------------------------------- |
| Register dari browser SPA                | `201`, user tersimpan di SQLite |
| Cek session `/api/v1/user` dengan cookie | `200`, session auth bekerja     |
| `vue-tsc --build`                        | 0 error                         |
| `php artisan test`                       | hijau                           |

## Keputusan & Konfigurasi Penting

| Hal                 | Nilai / Lokasi                                                               |
| ------------------- | ---------------------------------------------------------------------------- |
| Prefix API          | `api/v1` (`apiPrefix` di `api/bootstrap/app.php`)                            |
| Middleware stateful | `$middleware->statefulApi()` di `bootstrap/app.php`                          |
| CORS origin         | `[env('FRONTEND_URL')]` + `supports_credentials => true` (`config/cors.php`) |
| Stateful domain     | `SANCTUM_STATEFUL_DOMAINS=localhost:5173` (`.env`)                           |
| Session domain      | `SESSION_DOMAIN=localhost` (`.env`)                                          |
| Proxy Vite          | `/api` → `http://localhost:8000` (`web/vite.config.ts`)                      |

## Pelajaran Penting (untuk fase berikutnya)

1. **Sanctum stateful check** butuh header `Origin` atau `Referer` yang cocok dengan `sanctum.stateful`. Browser mengirim otomatis; curl/Postman harus manual.
2. **`Auth::login()` wajib** dipanggil setelah register/login agar session terikat user — tanpa ini semua request berikutnya `401`.
3. Route CSRF Sanctum ada di `/sanctum/csrf-cookie` (**di luar** prefix `api/v1`) — proxy Vite menangani `/api` saja, jadi FE memanggil via proxy `/api/sanctum/csrf-cookie`.
4. PHP lokal via Herd Lite: `export PATH="/c/Users/ASUS TUF/.config/herd-lite/bin:$PATH"` sebelum perintah `php`/`composer`.

## File Terkait

- Backend: `api/bootstrap/app.php`, `api/config/cors.php`, `api/routes/api.php`, `api/database/migrations/*_create_cvs_table.php`
- Frontend: `web/vite.config.ts`, `web/src/stores/auth.ts`, `web/src/views/AuthTestView.vue`

→ Lanjut ke [Fase 1 — Auth](phase-1-auth.md)
