# Fase 5 — PDF

> Status: **Belum mulai** · Estimasi: 1 hari · Prasyarat: [Fase 4](phase-4-ai-summary.md)

## Rencana Kerja

- [ ] `PdfService` (Browsershot) render template yang sama dengan preview
- [ ] Endpoint `/cvs/{id}/pdf` + tombol download
- [ ] Install Chromium lokal untuk testing

## Referensi

- Kontrak: [API Spec — PDF](../API_SPEC.md#pdf)
- ADR-4: Browsershot + headless Chrome; DomPDF/wkhtmltopdf tidak dipakai (rusak di Tailwind modern). Template HTML yang sama dengan Fase 3: `modern` (VitaeKit) / `classic` (LumiCV Minimal).
- Nama file: `{nama}_CV.pdf`, timeout render 30s → `504`.

## Definisi Selesai

- PDF hasil download **identik** dengan preview.
- Kriteria MVP terkait PDF di [PRD](../PRD.md#7-kriteria-selesai-mvp) terpenuhi.

← [Fase 4](phase-4-ai-summary.md) · Lanjut ke [Fase 6](phase-6-landing-polish.md)
