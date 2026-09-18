# Fase 5 — PDF

> Status: **Selesai** · Estimasi: 1 hari · Prasyarat: [Fase 4](phase-4-ai-summary.md)

## Rencana Kerja

- [x] `PdfService` (Browsershot) render template yang sama dengan preview
- [x] Endpoint `/cvs/{id}/pdf` + tombol download
- [x] Install Chromium lokal untuk testing

## Implementasi

- `api/app/Services/PdfService.php`: menerima HTML print dan memanggil `Browsershot::html($html)` dengan A4, margin 14/16/14/16mm, `showBackground()`, dan `waitUntilNetworkIdle()`. Browser memakai Edge Chromium lokal melalui `useChrome()->setChromePath()` apabila tersedia, lalu fallback ke Puppeteer.
- Shell HTML Browsershot adalah `file://` sementara. `PdfService` menambahkan argumen Chromium `disable-web-security` dan `allow-file-access-from-files` supaya module Vite atau aset build dari `FRONTEND_URL` tetap termuat. Tanpa argumen ini PDF dapat menjadi halaman kosong karena module script diblokir CORS.
- Paginasi tanpa elemen terpotong (2026-09-08): print CSS di `CvPreview.vue` menambah `header, section { break-inside: avoid; page-break-inside: avoid }` — section yang tidak muat pindah utuh ke halaman berikutnya, tidak ada judul section/entry terpotong di tengah antar halaman. Preview editor memakai prop `paged` dengan break point yang sama (per batas `header`/`section`, lebar konten 673px, tinggi halaman 1017px) sehingga preview dan PDF memotong halaman di titik yang sama.
- Ketajaman teks print (2026-09-18): blok `@media print` di `CvPreview.vue` mengembalikan subpixel AA bawaan (`-webkit-font-smoothing: auto`, `-moz-osx-font-smoothing: auto`) dan menambah `text-rendering: geometricPrecision` pada `.cv-page`/`.a4-page-inner` agar glyph lebih geometris saat dirender Chromium. Hanya berlaku media print, preview layar tidak berubah. Teks tetap vektor (tidak ada rasterisasi). Blur yang muncul saat membuka PDF di viewer browser (Edge, makin terasa saat zoom-out) adalah artefak rasterisasi canvas viewer, bukan cacat file — lihat `DESIGN.md` §4 untuk gejala pembeda dan uji cepat.
- `GET /api/v1/cvs/{cv}/pdf` melalui `CvController@pdf`: cek pemilik CV, membuat shell dengan `resolvePrintHtml()`, lalu meneruskannya ke `PdfService::render($html)`. Jalur ini tidak lagi meminta endpoint API kedua, sehingga tidak deadlock pada Laravel development server satu-proses. Nama file tetap `{nama}_CV.pdf`, disanitasi, dengan `Content-Disposition: attachment` dan `application/pdf`.
- `GET /api/v1/cvs/{cv}/print` melalui `CvController@print` memakai middleware `signed`, lalu me-return `print.html` dengan `window.__CV_DATA__`/`__CV_TEMPLATE__`/`__CV_LANGUAGE__` ter-embed. Route ini hanya untuk inspeksi shell internal, bukan dipanggil `PdfService` atau frontend.
- Frontend: tombol **PDF** di kartu CV (Dashboard) dan **Download PDF** di header preview (halaman edit) memakai `window.open` dengan cookie session.

## Referensi

- Kontrak: [API Spec: PDF](../API_SPEC.md#pdf)
- ADR-4: Browsershot + headless Chrome; DomPDF/wkhtmltopdf tidak dipakai (rusak di Tailwind modern). Template HTML yang sama dengan Fase 3: `modern` (VitaeKit) / `classic` (LumiCV Minimal) / `neon` (dokumen satu kolom, divider mint `#6ee7b7`, grid kontak responsif, foto persegi opsional, tanpa QR atau border luar).
- Nama file: `{nama}_CV.pdf`. Batas waktu mengikuti konfigurasi proses PHP dan Browsershot di environment deploy.

## Definisi Selesai

- [x] PDF hasil download memakai markup `CvPreview` yang sama dengan preview.
- [x] Endpoint PDF Neon diverifikasi setelah redesign: `200`, `application/pdf`, signature `%PDF-`, header `Content-Disposition: attachment`, 69.973 byte, dan konten CV ter-render.
- [x] Kriteria MVP terkait PDF di [PRD](../PRD.md#7-kriteria-selesai-mvp) terpenuhi.

← [Fase 4](phase-4-ai-summary.md) · Lanjut ke [Fase 6](phase-6-landing-polish.md)
