# Fase 5 — PDF

> Status: **Selesai** · Estimasi: 1 hari · Prasyarat: [Fase 4](phase-4-ai-summary.md)

## Rencana Kerja

- [x] `PdfService` (Browsershot) render template yang sama dengan preview
- [x] Endpoint `/cvs/{id}/pdf` + tombol download
- [x] Install Chromium lokal untuk testing

## Implementasi

- `api/app/Services/PdfService.php`: `Browsershot::html(view('pdf.cv'))` → A4, margin 14/16/14/16mm, `showBackground()`, `waitUntilNetworkIdle()`. Browser: Edge (Chromium) lokal via `useChrome()->setChromePath()` karena Chrome tidak terpasang; fallback ke Puppeteer default.
- `api/resources/views/pdf/cv.blade.php`: template Blade mandiri (CSS inline, tanpa Tailwind) yang meniru markup `CvPreview.vue` — struktur, warna, dan spacing identik untuk `modern` (navy underline, sans) dan `classic` (serif, uppercase nama, garis section).
- `GET /api/v1/cvs/{cv}/pdf` → `CvController@pdf`: owner check, nama file `{nama}_CV.pdf` (sanitasi), `Content-Disposition: attachment`, `application/pdf`.
- Frontend: tombol **PDF** di kartu CV (Dashboard) dan **Download PDF** di header preview (halaman edit) — `window.open` dengan cookie session.
- Puppeteer diinstall di `api/` (`npm i puppeteer`) sebagai fallback browser.

## Referensi

- Kontrak: [API Spec — PDF](../API_SPEC.md#pdf)
- ADR-4: Browsershot + headless Chrome; DomPDF/wkhtmltopdf tidak dipakai (rusak di Tailwind modern). Template HTML yang sama dengan Fase 3: `modern` (VitaeKit) / `classic` (LumiCV Minimal).
- Nama file: `{nama}_CV.pdf`, timeout render 30s → `504`.

## Definisi Selesai

- [x] PDF hasil download **identik** dengan preview (verified: text extraction + screenshot, 2 halaman, semua section muncul).
- [x] Kriteria MVP terkait PDF di [PRD](../PRD.md#7-kriteria-selesai-mvp) terpenuhi.

← [Fase 4](phase-4-ai-summary.md) · Lanjut ke [Fase 6](phase-6-landing-polish.md)
