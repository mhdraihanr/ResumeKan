# Fase 4 — AI Summary

> Status: **Belum mulai** · Estimasi: ½ hari · Prasyarat: [Fase 3](phase-3-preview-template.md)

## Rencana Kerja

- [ ] `GeminiService` + endpoint `/ai/summary` + throttle 5/menit
- [ ] Tombol "Generate" di bagian ringkasan; hasil bisa diedit

## Referensi

- Kontrak: [API Spec — AI](../API_SPEC.md#ai)
- ADR-5: `Http::post()` langsung, tanpa SDK.
- `.env`: `GEMINI_API_KEY` (belum diisi), `GEMINI_MODEL=gemini-2.0-flash`

## Definisi Selesai

- Klik Generate → ringkasan relevan muncul ≤ 5 detik, bisa diedit manual.
- Request ke-6 dalam 1 menit → `429`.
- Gemini down → `502 { "message": "AI service unavailable" }`.

← [Fase 3](phase-3-preview-template.md) · Lanjut ke [Fase 5](phase-5-pdf.md)
