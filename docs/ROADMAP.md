# Roadmap — ResumeKan

> Urutan pengerjaan MVP. Setiap fase = bisa diuji manual sebelum lanjut. Alur uji: [TESTING.md](TESTING.md).
> Ringkasan detail tiap fase: [docs/phases/](phases/) — [Fase 0](phases/phase-0-scaffold.md) · [1](phases/phase-1-auth.md) · [2](phases/phase-2-crud-cv.md) · [3](phases/phase-3-preview-template.md) · [4](phases/phase-4-ai-summary.md) · [5](phases/phase-5-pdf.md) · [6](phases/phase-6-landing-polish.md)

## Fase 0 — Scaffold (½ hari) ✅ [detail](phases/phase-0-scaffold.md)

- [x] `laravel new api` + Sanctum, migrasi `users` & `cvs`
- [x] `npm create vue@latest web` (TS + Router + Pinia) + Tailwind v4
- [x] Proxy dev Vite → `localhost:8000`, CORS & stateful domain

**Selesai bila:** register/login dari SPA berhasil menyimpan user. ✅ (terverifikasi dari browser)

## Fase 1 — Auth (½ hari) ✅ [detail](phases/phase-1-auth.md)

- [x] Endpoint register/login/logout/user
- [x] Pinia store auth + guard route `/dashboard`

## Fase 2 — CRUD CV (1 hari) ✅ [detail](phases/phase-2-crud-cv.md)

- [x] Migrasi `cvs` + Form Request validasi skema JSON
- [x] Endpoint CRUD + batas 10 CV
- [x] Halaman dashboard daftar CV + form buat/edit
- [x] Port logika form CV dari Applyin (React → Vue `<script setup>`)

## Fase 3 — Preview & Template (1 hari) ✅ [detail](phases/phase-3-preview-template.md)

- [x] Komponen `CvPreview` dengan 2 template (modern, classic) — VitaeKit Modern / LumiCV Minimal
- [x] Preview real-time saat mengisi form (computed dari store)

## Fase 4 — AI Summary (½ hari) [detail](phases/phase-4-ai-summary.md)

- [ ] `GeminiService` + endpoint `/ai/summary` + throttle 5/menit
- [ ] Tombol "Generate" di bagian ringkasan; hasil bisa diedit

## Fase 5 — PDF (1 hari) [detail](phases/phase-5-pdf.md)

- [ ] `PdfService` (Browsershot) render template yang sama dengan preview
- [ ] Endpoint `/cvs/{id}/pdf` + tombol download
- [ ] Install Chromium lokal untuk testing

## Fase 6 — Landing & Polish (1 hari) [detail](phases/phase-6-landing-polish.md)

- [ ] Landing page (hero, fitur, CTA) + animasi ringan (@vueuse/motion)
- [ ] Dark mode, responsive check, empty states

## Deploy (nanti, bukan MVP)

- [ ] FE: Vercel/Netlify · BE: VPS murah atau Railway (Browsershot butuh Chromium)
- [ ] DB: Neon Postgres — tinggal ganti `.env`

## Total estimasi: ±5 hari kerja
