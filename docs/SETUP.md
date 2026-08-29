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

AI_API_KEY=                    # dari dashboard AI gateway kamu
AI_BASE_URL=https://api.example.com/v1  # OpenAI-compatible gateway (ganti sesuai provider)
AI_MODEL=provider/model-name   # mis. anthropic/claude-3-haiku, openai/gpt-4o-mini — ganti tanpa ubah kode

CV_MAX_PER_USER=10
AI_THROTTLE_PER_MINUTE=5
```

## Menjalankan Development (harian)

Butuh 2 terminal:

```bash
# Terminal 1 — API http://127.0.0.1:8000
cd api
php artisan serve
# alternatif: php artisan dev (serve + queue + vite) — butuh `cd api && npm install` dulu

# Terminal 2 — Web http://127.0.0.1:5173
cd web
pnpm dev

# Cek
curl http://127.0.0.1:8000/up   # → 200
# buka http://127.0.0.1:5173
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
- [ ] `AI_API_KEY` tidak pernah masuk git
