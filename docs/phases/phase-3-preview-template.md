# Fase 3 — Preview & Template

> Status: **Selesai** (2026-08-27) · Estimasi: 1 hari · Prasyarat: [Fase 2](phase-2-crud-cv.md) ✅

## Yang Dikerjakan

- [x] `CvPreview.vue` — 3 template ATS (modern/classic/neon), 1 template = 1 file (`CvModern.vue`/`CvClassic.vue`/`CvNeon.vue`, header include masing-masing), HTML/CSS murni sama untuk preview & PDF (ADR-4)
- [x] Preview real-time — `v-model` data/template dari `CvFormView.vue`, update tanpa lag
- [x] Layout split `lg:grid-cols-[520px_1fr]` — form kiri scroll, preview kanan sticky; stack di mobile

## Hasil Implementasi

| File                                  | Isi                                                                                                                                                                                                                              |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------- | ----------------------- |
| `web/src/components/cv/CvPreview.vue` | Props `data: CvData`, `template: string` — router ke `templates/CvModern.vue`/`CvClassic.vue`/`CvNeon.vue` via `comp` computed (refactor 2026-08-31: 1 template = 1 file, header include masing-masing; `sections/PreviewSection | EntryRow | BulletList.vue` shared) |
| `web/src/views/CvFormView.vue`        | Grid form + preview, `CvPreview :data="data" :template="template"`                                                                                                                                                               |

## Hasil Verifikasi

| Uji                                                                    | Hasil   |
| ---------------------------------------------------------------------- | ------- |
| `vue-tsc --build`                                                      | 0 error |
| `pnpm build`                                                           | ok      |
| Browser: ketik Nama → preview update                                   | ✅      |
| Browser: switch Modern ↔ Classic ↔ Neon → header/accent/layout berubah | ✅      |
| Mobile: stack form di atas preview                                     | ✅      |

## Referensi Template

> Dipilih sebelum eksekusi — diverifikasi via Exa 2026-08-27.

| Template  | Referensi                                               | URL                                                                 |
| --------- | ------------------------------------------------------- | ------------------------------------------------------------------- |
| `modern`  | VitaeKit Modern — Clean sans-serif with a blue accent   | https://vitaekit.com/resume-templates/modern                        |
| `classic` | LumiCV Minimal — Distraction-free, clean hierarchy      | https://lumicv.com/resume-templates/minimal                         |
| `neon`    | Referensi HTML internal, dokumen profesional satu kolom | [gemini-code-1788187543370.html](../gemini-code-1788187543370.html) |

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

### Neon (referensi HTML internal) - satu kolom

- Dokumen putih satu kolom dengan teks utama `#111`, teks pendukung `#444`, dan divider mint `#6ee7b7` di setiap heading section
- Header kiri: nama 42px, subjudul dari posisi pengalaman terbaru, grid kontak dua kolom yang menjadi satu kolom pada layar sempit
- Foto persegi `110px × 135px` dari `personal.photo` bersifat opsional (di-upload via Cloudinary). Bila tidak ada foto, header tetap rapi. Tidak ada QR atau border luar
- Bahasa dan sertifikat dapat berdampingan pada layar lebar, tetapi konten utama tetap mengalir linear
- **Multi-bahasa (F7, 2026-09-02):** semua judul section mengikuti `language` pilihan via `web/src/lib/cv-labels.ts` (`id`/`en`) di preview & PDF (`__CV_LANGUAGE__`); konten user tidak diterjemahkan.
- Cocok: CV profesional modern yang mengutamakan hierarchy visual dan keterbacaan ATS

### Aturan ATS (semua template)

Ketiga template memakai body satu kolom dengan reading order = parsing order, heading standar (`Ringkasan`, `Pengalaman Kerja`, `Pendidikan`, `Keahlian`), kontak berbentuk teks/link, font standar, tanpa tables, text boxes, QR, graphics, atau skill meters. Accent via CSS border, bukan image.

## Catatan

Catatan enhancement historis di bawah yang menyebut "kedua template" merujuk Modern dan Classic sebelum Neon dibuat ulang. Neon mengikuti bagian referensi Neon di atas.

- Template harus **HTML/CSS murni yang sama** dengan yang dirender PDF di Fase 5 (ADR-4). Jangan pakai fitur CSS yang tidak didukung headless Chrome print.
- Preview = computed dari Pinia store `cv`, tanpa state terpisah.
- Palet: 2–3 warna netral + 1 accent (antislop-ui R-29) — modern navy `#1e40af` border-b-2, classic `slate-900` border-b-[1.5px] (garis berwarna di bawah title, 8 section seragam incl. Organisasi).
- Form value font `text-xs` (12px) — label `text-xs`, section title `text-sm` tetap; input `py-1.5` agar density pas di lebar 480/520.
- Enhancement 2026-08-28 — IPK: `education[].gpa` opsional, render `IPK: ...` di bawah institusi (ATS: di dalam Education). Organisasi: `organizations[]` max 5, section terpisah `Organisasi`/`Organizations` setelah Pendidikan, `border-b-[1.5px]` seragam, bullets newline. Skills: label `Hard skills:` / `Soft skills:` di kedua template (modern & classic). Form: label di luar kolom, placeholder hanya example (WCAG 3.3.2, `placeholder:text-slate-400`).
- Enhancement 2026-08-29 — Employment Type: `experiences[].employmentType` opsional (Full-time/Part-time/Internship/Contract/Freelance, label EN), select di form, render `· Full-time` di samping posisi di kedua template (ATS: literal "Intern" di title line, Workday/iCIMS de-weight internship di section terpisah — jadi tetap di Experience). Summary: title "Ringkasan" dihapus di kedua template — teks langsung di bawah header (ATS: summary tanpa heading, reading order tetap).
- Enhancement 2026-08-30 — Employment Type layout: pindah dari title line (`Posisi · Perusahaan · Full-time`) ke baris metadata di bawahnya, di awal sebelum lokasi: `Full-time · Jakarta, Indonesia`. Title line bersih hanya posisi + perusahaan (ATS: job title + company lebih mudah diparse), metadata (type + lokasi) satu baris, konsisten dengan pola Education (baris 1 gelar, baris 2 institusi·lokasi). Edge case: type/lokasi kosong → baris menyesuaikan, dua-duanya kosong → baris hilang. Berlaku kedua template (modern + classic) — sekali fix di `CvPreview.vue` tercover hero, editor, dan PDF.
- Enhancement 2026-08-30 — Organisasi layout: tukar urutan baris dari `Peran · Organisasi` (baris 1) ke `Organisasi` (bold, baris 1) + `Peran` (slate-500, baris 2). Periode tetap di baris 1 kanan. Alasan: organisasi sebagai entitas utama di baris 1 (konsisten dengan pola Experience dan Education — entitas di atas, detail di bawah), peran sebagai detail posisi di baris 2. Edge case: peran kosong → baris 2 hilang. Berlaku kedua template.
- Enhancement 2026-08-30 — Proyek layout: role dipindah dari inline di title line (`Judul · Peran`) ke baris sendiri di atas tech stack dengan label eksplisit `Peran:` (slate-500). Struktur: baris 1 judul (bold), baris 2 objective (slate-700), baris 3 `Peran: ...` (labeled), baris 4 `Tech Stack: ...` (labeled). Dasar Exa ATScore (name → description → tools → outcome) dan TMJ Studio (stack di akhir, lead dengan outcome). Edge case: role kosong → baris hilang. Berlaku kedua template.
- Enhancement 2026-08-29 — Antislop R-02: hapus em dash `—` dari semua teks render di form & preview (ganti separator jadi `·` atau `-`): posisi·perusahaan, tanggal `-`, degree·major, role·organisasi, proyek·role, placeholder tanggal `2020 - 2024`, label `Deskripsi (1 baris = 1 bullet)`. Em dash tersisa hanya di HTML comment (tidak dirender).- Enhancement 2026-08-29 — Education layout: `education[].location` baru (opsional, kota). Preview: institusi di baris paling atas (bold), di bawahnya sejajar `gelar · jurusan · lokasi · IPK` (IPK/lokasi slate-500), tahun tetap kanan. Berlaku kedua template.
- Enhancement 2026-08-29 — Education ATS (Exa ATScore): gelar & jurusan digabung di baris pertama (bold) karena ATS mengoptimalkan degree di posisi pertama, institusi di baris kedua. Form: satu input `Gelar & Jurusan` (placeholder ID/EN: `S1 Teknik Informatika` / `Bachelor of Science in Computer Science`). Baris 2: `institusi · lokasi · IPK`. Field `major` dihapus total (type, form, validasi BE) — data lama dengan `major` tetap render via `degree` saja.
- Enhancement 2026-08-29 — Education achievements (Exa The Muse): `education[].achievements` jadi textarea `Prestasi / Deskripsi (1 baris = 1 bullet)` max 1000, render sebagai bullet list `list-disc` konsisten dengan Experience/Organisasi (bukan teks abu). Warna secondary detail education (location · IPK) diubah slate-500 → slate-700 agar konsisten dengan section lain. Organisasi tetap section terpisah (dedicated section lebih ATS-friendly untuk banyak org).
- Enhancement 2026-08-29 — Education margin konsisten (Exa ATScore + ResumeOptimizerPro): IPK dipindah ke baris sendiri `IPK: X.X/4.0` (bukan inline di baris institusi) — ATS: GPA di baris sendiri langsung di bawah institusi, konsisten dengan struktur Experience (title / subtitle / detail). Struktur education: baris 1 gelar (bold), baris 2 `institusi · lokasi`, baris 3 `IPK: ...`, lalu bullet achievements. Berlaku kedua template.
- Enhancement 2026-08-31 — Header kontak 2 baris semantik (kedua template): pecah dari satu baris `flex-wrap` (yang wrap acak + `·` bisa gantung di ujung baris) jadi 2 baris terpisah — baris 1 kontak langsung (`email · phone · lokasi`), baris 2 link online (`linkedin · website · github`). Dasar: pola resume profesional (contact line + links line), tahan jumlah field berapa pun (1–6 tetap rapi, baris kosong di-skip), ATS tetap aman (teks linear + `·`, bukan table/column — sesuai ADR tanpa tables/columns/graphics). Kalau semua kosong → fallback `email · phone · alamat`. Drop `contactLine` computed (tidak dipakai lagi).
- Enhancement 2026-08-31 — Bullet lebih jelas (kedua template, Exa ResumeOptimizerPro/HireFlow): list bullet tetap `list-disc` standar (bullet `•` ATS-safe), tapi `marker:text-slate-500` (marker lebih gelap dari teks — sebelumnya abu muda hampir tak terlihat), `space-y-1` (jarak antar bullet, sebelumnya 0), `pl-1` pada `li` (indentasi teks dari marker), `mt-1.5` (sebelumnya `mt-1`). Masih plain round bullet, bukan symbol non-standar — aman di semua ATS.
- Enhancement 2026-08-29 — Education sejajar dengan section lain: struktur diubah dari `flex justify-between` (konten kiri + tahun kanan) ke pola Experience/Organisasi — `flex items-baseline justify-between` hanya untuk baris gelar+tahun, lalu konten (institusi·lokasi, IPK, bullet achievements) full-width di bawahnya. Ini yang bikin deskripsi pendidikan tidak sejajar dengan section lain.
- Enhancement 2026-08-29 — Simpan Draft: tombol `Simpan Draft` di header preview (kanan atas, sebelah Download PDF) — save via PUT/POST tanpa keluar form, toast `Draft tersimpan` (2.6s, slate-900/red-600 untuk error). CV baru: create lalu `cvId` di-set (heading jadi "Edit CV", tombol Download PDF muncul) tanpa navigasi. Rekomendasi UX dari GitLab Pajamas (saving drafts: toast konfirmasi, tetap di halaman).

## Definisi Selesai

- Mengubah field form langsung tercermin di preview tanpa lag.
- Switch template modern ↔ classic ↔ neon mengubah tampilan header, heading, accent, dan divider tanpa mengubah body linear.
- Preview identik dengan output PDF Fase 5 (HTML yang sama).

← [Fase 2](phase-2-crud-cv.md) · Lanjut ke [Fase 4](phase-4-ai-summary.md)
