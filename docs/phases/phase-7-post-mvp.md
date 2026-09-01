# Fase 7 — Post-MVP Backlog (setelah MVP selesai)

> Status: **Backlog** · Estimasi: 1–1.5 hari · Prasyarat: [Fase 6](phase-6-landing-polish.md) ✅ + MVP deploy
> Ditahan dulu — jangan kerjakan sebelum Fase 4–6 selesai. Ditambahkan 2026-08-28.

## Tujuan

Menampung ide yang ditunda demi MVP ramping (YAGNI). Dieksekusi hanya jika ada sinyal demand pasca-launch.

## Backlog

### 7a. Import CV dari PDF/DOCX → auto-isi form (ditahan)

- **Masalah:** user yang sudah punya CV (pekerja, target PRD #2) malas ketik ulang.
- **Solusi (paling lazy):** upload `multipart/form-data` → `smalot/pdfparser` extract text lokal → kirim text ke `AiService` (OpenAI-compatible, model via `AI_MODEL`) untuk strukturkan ke `CvData` JSON → isi `local` form (tidak auto-save, user review & edit dulu).
- **Batasan v1:** max 5MB / 5 halaman, PDF text-based saja (scan/image → tolak "upload PDF text-based"), throttle 3/menit, file tidak disimpan, `502` jika gagal.
- **Kontrak (rencana):** `POST /api/v1/ai/parse-cv` — body `file` (pdf/docx), response `{ data: CvData }` + warning jika field kosong.
- **Dampak docs saat dieksekusi:** `PRD.md` F2/F5, `API_SPEC.md` tambah `POST /ai/parse-cv`, `ARCHITECTURE.md` ADR-5 tambah file handling + `pdfparser`, `DATA_MODEL.md` tetap (mapping ke skema existing).
- **Kriteria mulai:** banyak request "punya CV lama" setelah launch, atau Fase 4 summary sudah stabil.

### Ide lain (belum diprioritaskan)

- Template premium / paywall
- Share link publik CV
- OAuth Google/LinkedIn
- ATS score checker

## Catatan

- Fase 4 (AI Summary) tetap prioritas berikutnya setelah Fase 3 — jangan loncat ke 7a dulu.
- Saat eksekusi 7a, tambah 1 komponen upload di atas `CvForm.vue` ("Import dari PDF") + 1 endpoint, tanpa ubah migrasi DB.

← [Fase 6](phase-6-landing-polish.md) · Kembali ke [Roadmap](../ROADMAP.md)
