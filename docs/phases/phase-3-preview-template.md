# Fase 3 — Preview & Template

> Status: **Selesai** (2026-08-27) · Estimasi: 1 hari · Prasyarat: [Fase 2](phase-2-crud-cv.md) ✅

## Yang Dikerjakan

- [x] `CvPreview.vue` — 2 template ATS (modern/classic), HTML/CSS murni sama untuk preview & PDF (ADR-4)
- [x] Preview real-time — `v-model` data/template dari `CvFormView.vue`, update tanpa lag
- [x] Layout split `lg:grid-cols-[520px_1fr]` — form kiri scroll, preview kanan sticky; stack di mobile

## Hasil Implementasi

| File                                  | Isi                                                                                     |
| ------------------------------------- | --------------------------------------------------------------------------------------- |
| `web/src/components/cv/CvPreview.vue` | Props `data: CvData`, `template: string` — modern (VitaeKit) / classic (LumiCV Minimal) |
| `web/src/views/CvFormView.vue`        | Grid form + preview, `CvPreview :data="data" :template="template"`                      |

## Hasil Verifikasi

| Uji                                                      | Hasil   |
| -------------------------------------------------------- | ------- |
| `vue-tsc --build`                                        | 0 error |
| `pnpm build`                                             | ok      |
| Browser: ketik Nama → preview update                     | ✅      |
| Browser: switch Modern ↔ Classic → header/accent berubah | ✅      |
| Mobile: stack form di atas preview                       | ✅      |

## Referensi Template

> Dipilih sebelum eksekusi — diverifikasi via Exa 2026-08-27.

| Template  | Referensi                                             | URL                                          |
| --------- | ----------------------------------------------------- | -------------------------------------------- |
| `modern`  | VitaeKit Modern — Clean sans-serif with a blue accent | https://vitaekit.com/resume-templates/modern |
| `classic` | LumiCV Minimal — Distraction-free, clean hierarchy    | https://lumicv.com/resume-templates/minimal  |

### Modern (VitaeKit) — PDF-like

- Font sans-serif (Inter / Helvetica), single-column, header kiri (nama + kontak `email · phone · city · LinkedIn`)
- Accent navy `#1e40af` — garis bawah heading section (CSS `border`, bukan image)
- Bullet `•` hitam, dates rata kanan, leading longgar (6–7 bullet/role tetap napas)
- A4 print CSS (`@page` A4, margin 14mm/16mm) — preview identik dengan PDF Browsershot
- Kontak LinkedIn/Website/GitHub: link klikable, dukung `www.` tanpa `https://` (display tanpa scheme, href dinormalisasi)
- Cocok: tech, SaaS, startup, PM/designer/data — sinyal "product-design-aware"

### Classic (image.png — monochrome)

- Single-column, whitespace lega, hierarchy tipografi saja — tanpa warna/sidebar/icon
- Nama uppercase `ALEX JOHNSON`, header centered, kontak `·` separator
- Section title uppercase + garis `border-b-[1.5px] border-slate-900` berwarna di bawah title (bukan hairline abu) — 8 section seragam 1.5px incl. Organisasi
- Skills pisah `Hard skills:` / `Soft skills:` (ATS: hard dominan, soft dibuktikan di bullets), Certificates/Languages pisah section
- Real-text PDF, lolos Workday/Greenhouse/Lever/iCIMS/Taleo
- LinkedIn/Website/GitHub: sama seperti modern — dukung `www.` tanpa scheme
- Cocok: konservatif, ATS-heavy, lintas industri — "safest choice"

### Aturan ATS (keduanya)

Single-column, reading order = parsing order, heading standar (`Experience`, `Education`, `Skills`), kontak di body, font standar, tanpa tables/text boxes/columns/graphics/skill meters. Accent via CSS border, bukan image.

## Catatan

- Template harus **HTML/CSS murni yang sama** dengan yang dirender PDF di Fase 5 (ADR-4) — jangan pakai fitur CSS yang tidak didukung headless Chrome print.
- Preview = computed dari Pinia store `cv`, tanpa state terpisah.
- Palet: 2–3 warna netral + 1 accent (antislop-ui R-29) — modern navy `#1e40af` border-b-2, classic `slate-900` border-b-[1.5px] (garis berwarna di bawah title, 8 section seragam incl. Organisasi).
- Form value font `text-xs` (12px) — label `text-xs`, section title `text-sm` tetap; input `py-1.5` agar density pas di lebar 480/520.
- Enhancement 2026-08-28 — IPK: `education[].gpa` opsional, render `IPK: ...` di bawah institusi (ATS: di dalam Education). Organisasi: `organizations[]` max 5, section terpisah `Organisasi`/`Organizations` setelah Pendidikan, `border-b-[1.5px]` seragam, bullets newline. Skills: label `Hard skills:` / `Soft skills:` di kedua template (modern & classic). Form: label di luar kolom, placeholder hanya example (WCAG 3.3.2, `placeholder:text-slate-400`).
- Enhancement 2026-08-29 — Employment Type: `experiences[].employmentType` opsional (Full-time/Part-time/Internship/Contract/Freelance, label EN), select di form, render `· Full-time` di samping posisi di kedua template (ATS: literal "Intern" di title line, Workday/iCIMS de-weight internship di section terpisah — jadi tetap di Experience). Summary: title "Ringkasan" dihapus di kedua template — teks langsung di bawah header (ATS: summary tanpa heading, reading order tetap).
- Enhancement 2026-08-29 — Antislop R-02: hapus em dash `—` dari semua teks render di form & preview (ganti separator jadi `·` atau `-`): posisi·perusahaan, tanggal `-`, degree·major, role·organisasi, proyek·role, placeholder tanggal `2020 - 2024`, label `Deskripsi (1 baris = 1 bullet)`. Em dash tersisa hanya di HTML comment (tidak dirender).- Enhancement 2026-08-29 — Education layout: `education[].location` baru (opsional, kota). Preview: institusi di baris paling atas (bold), di bawahnya sejajar `gelar · jurusan · lokasi · IPK` (IPK/lokasi slate-500), tahun tetap kanan. Berlaku kedua template.
- Enhancement 2026-08-29 — Education ATS (Exa ATScore): gelar & jurusan digabung di baris pertama (bold) karena ATS mengoptimalkan degree di posisi pertama, institusi di baris kedua. Form: satu input `Gelar & Jurusan` (placeholder ID/EN: `S1 Teknik Informatika` / `Bachelor of Science in Computer Science`). Baris 2: `institusi · lokasi · IPK`. Field `major` dihapus total (type, form, validasi BE) — data lama dengan `major` tetap render via `degree` saja.
- Enhancement 2026-08-29 — Education achievements (Exa The Muse): `education[].achievements` jadi textarea `Prestasi / Deskripsi (1 baris = 1 bullet)` max 1000, render sebagai bullet list `list-disc` konsisten dengan Experience/Organisasi (bukan teks abu). Warna secondary detail education (location · IPK) diubah slate-500 → slate-700 agar konsisten dengan section lain. Organisasi tetap section terpisah (dedicated section lebih ATS-friendly untuk banyak org).
- Enhancement 2026-08-29 — Education margin konsisten (Exa ATScore + ResumeOptimizerPro): IPK dipindah ke baris sendiri `IPK: X.X/4.0` (bukan inline di baris institusi) — ATS: GPA di baris sendiri langsung di bawah institusi, konsisten dengan struktur Experience (title / subtitle / detail). Struktur education: baris 1 gelar (bold), baris 2 `institusi · lokasi`, baris 3 `IPK: ...`, lalu bullet achievements. Berlaku kedua template.
- Enhancement 2026-08-29 — Education sejajar dengan section lain: struktur diubah dari `flex justify-between` (konten kiri + tahun kanan) ke pola Experience/Organisasi — `flex items-baseline justify-between` hanya untuk baris gelar+tahun, lalu konten (institusi·lokasi, IPK, bullet achievements) full-width di bawahnya. Ini yang bikin deskripsi pendidikan tidak sejajar dengan section lain.
- Enhancement 2026-08-29 — Simpan Draft: tombol `Simpan Draft` di header preview (kanan atas, sebelah Download PDF) — save via PUT/POST tanpa keluar form, toast `Draft tersimpan` (2.6s, slate-900/red-600 untuk error). CV baru: create lalu `cvId` di-set (heading jadi "Edit CV", tombol Download PDF muncul) tanpa navigasi. Rekomendasi UX dari GitLab Pajamas (saving drafts: toast konfirmasi, tetap di halaman).

## Definisi Selesai

- Mengubah field form langsung tercermin di preview tanpa lag.
- Switch template modern ↔ classic mengubah tampilan (header, heading, accent, divider).
- Preview identik dengan output PDF Fase 5 (HTML yang sama).

← [Fase 2](phase-2-crud-cv.md) · Lanjut ke [Fase 4](phase-4-ai-summary.md)
