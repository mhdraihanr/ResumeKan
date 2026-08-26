# Fase 3 — Preview & Template

> Status: **Belum mulai** · Estimasi: 1 hari · Prasyarat: [Fase 2](phase-2-crud-cv.md)

## Rencana Kerja

- [ ] Komponen `CvPreview` dengan 2 template (modern, classic)
- [ ] Preview real-time saat mengisi form (computed dari store)

## Catatan

- Template harus **HTML/CSS murni yang sama** dengan yang dirender PDF di Fase 5 (ADR-4) — jangan pakai fitur CSS yang tidak didukung headless Chrome print.
- Preview = computed dari Pinia store `cv`, tanpa state terpisah.

## Definisi Selesai

- Mengubah field form langsung tercermin di preview tanpa lag.
- Switch template modern ↔ classic mengubah tampilan.

← [Fase 2](phase-2-crud-cv.md) · Lanjut ke [Fase 4](phase-4-ai-summary.md)
