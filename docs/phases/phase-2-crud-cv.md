# Fase 2 — CRUD CV

> Status: **Belum mulai** · Estimasi: 1 hari · Prasyarat: [Fase 1](phase-1-auth.md)

## Rencana Kerja

- [ ] Migrasi `cvs` + Form Request validasi skema JSON
- [ ] Endpoint CRUD + batas 10 CV
- [ ] Halaman dashboard daftar CV + form buat/edit
- [ ] Port logika form CV dari Applyin (React → Vue `<script setup>`)

## Referensi

- Skema kolom & JSON `data`: [Data Model](../DATA_MODEL.md)
- Kontrak endpoint: [API Spec — CV](../API_SPEC.md#cv)
- Batas bisnis: maks 10 CV/user (`CV_MAX_PER_USER`), payload ≤ 50 KB

## Catatan dari Fase 0

- Migrasi `cvs` **sudah dibuat** di Fase 0 (kolom lengkap: `user_id, title, template, language, data`). Fase ini tinggal buat model logic + validasi.
- Model `App\Models\Cv` sudah ada dengan cast `data => array`; relasi `User::cvs()` sudah terdefinisi.

## Definisi Selesai

- Buat, lihat daftar, edit, hapus CV dari dashboard.
- CV ke-11 ditolak `422`.
- User tidak bisa akses CV milik user lain (`403`).

← [Fase 1](phase-1-auth.md) · Lanjut ke [Fase 3](phase-3-preview-template.md)
