# Setup — ResumeKan

> Prasyarat & konfigurasi environment sebelum Fase 0.

## Prasyarat

| Tool            | Versi    | Catatan                                |
| --------------- | -------- | -------------------------------------- |
| PHP             | ≥ 8.3    | ekstensi `sqlite3`, `pdo_sqlite` aktif |
| Composer        | 2.x      |                                        |
| Node.js         | ≥ 20 LTS |                                        |
| Chromium/Chrome | terbaru  | untuk Browsershot (Fase 5)             |

Windows: cara termudah install PHP+Composer via [Laravel Herd](https://herd.laravel.com) (free) atau Laragon.

## Environment Variables (api/.env)

```env
APP_NAME=ResumeKan
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=sqlite            # produksi: pgsql + DATABASE_URL Neon

SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:5173

GEMINI_API_KEY=                 # dari https://aistudio.google.com/apikey
GEMINI_MODEL=gemini-2.0-flash   # model gratis, cukup untuk summary

CV_MAX_PER_USER=10
AI_THROTTLE_PER_MINUTE=5
```

## Perintah Awal (dijalankan saat scaffold)

```bash
# Backend
composer create-project laravel/laravel api
cd api && composer require laravel/sanctum spatie/browsershot
php artisan install:api

# Frontend
pnpm create vue@latest web       # pilih: TypeScript, Router, Pinia
cd web && pnpm i -D tailwindcss @tailwindcss/vite
```

## Checklist sebelum commit pertama

- [ ] `.env` di `.gitignore` kedua project (default sudah)
- [ ] `GEMINI_API_KEY` tidak pernah masuk git
