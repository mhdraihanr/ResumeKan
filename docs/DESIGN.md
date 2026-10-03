# ResumeKan Design Guide (DESIGN.md)

> Sumber kebenaran arah visual ResumeKan. Semua file UI baru (landing, komponen, polish) wajib
> mengikuti dokumen ini. Di-update saat ada keputusan design baru. Disusun sesuai antislop
> core R-31 (dials + palette + reason lines) dan R-37 (design direction sebelum bangun UI).

## 1. Dials

| Dial   | Nilai    | Alasan                                                                                                                                                                                                   |
| ------ | -------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ENERGY | 2 dari 5 | Neobrutalism profesional: tegas lewat border tebal dan hard shadow, tenang lewat palet terbatas dan banyak ruang kosong. Terlalu tinggi terasa arcade (R-29), terlalu rendah kehilangan karakter brutal. |
| RHYTHM | 2 dari 5 | Section landing beruntun dengan variasi background (ink/paper/navy/powder), tinggi moderate (RHYTHM 2 per antislop layoutmobile).                                                                        |
| MOTION | 1 dari 5 | Hover press-down + scroll-reveal ringan. Nilai interaksi nyata, hindari motion dekoratif (R-12).                                                                                                         |

## 2. Style: Neo-Brutalism Profesional

Neobrutalism digunakan sebagai struktur, bukan sebagai sticker. Referensi arah:
[madegooddesigns.com](https://madegooddesigns.com/blogs/tech/neo-brutalism-web-design) dan
[alexmayhew.dev](https://alexmayhew.dev/blog/neo-brutalism-tailwind): border tebal, hard shadow
tanpa blur, warna flat, tapi tetap rapi dan profesional untuk audiens pencari kerja.

### Aturan visual

- Border: 2px solid ink untuk semua elemen UI; 3px untuk elemen fokus (input, tombol utama).
- Hard shadow tanpa blur: `4px 4px 0 0` (default), `2px 2px 0 0` (kecil), `6px 6px 0 0` (besar/hero).
- Warna flat. Tidak ada gradien, glow, atau glassmorphism (R-01).
- Radius maksimum `rounded-lg` (0.5rem). Tidak ada pill/penuh.
- Tombol: tekan turun (translate 2px, shadow mengecil) saat hover/active, kembali saat lepas.
  Tombol utama navy dengan teks putih. Interaksi wajib di semua tombol brutal (referensi tema Slab).
- Card fitur: border ink + hard shadow, tanpa gradien, ikon garis 24px.
- Tekstur dot grid halftone (`.bg-dots` di `main.css`): dot 1px size 20px, light `rgba(15,23,42,.46)`, dark `rgba(248,250,252,.24)`. Motif kertas CV (identitas produk). Dose cap: hero + FAQ + panel kanan auth (login & register, 4 permukaan paper); section ink & navy tetap flat untuk hierarki (R-07, R-12).

### Dilarang (antislop)

- Emoji, gradien, glow, purple/blue default Tailwind, glassmorphism (R-01, R-30).
- Em dash di copy (R-02). Gunakan koma, titik, atau kurung.
- Heading font kustom untuk gaya semata (R-06). Font stack default dengan weight ekstra.
- Paragraf panjang di landing. Max ~12 kata per baris.

## 3. Palet: Ink & Navy

> Pengganti amber yang awalnya diusulkan: user memilih powder blue sebagai accent kedua.

| Token  | Hex       | Fungsi                                              | Alasan                                                                                                                            |
| ------ | --------- | --------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Ink    | `#0f172a` | Border, teks utama, section gelap                   | Warna teks default preview dan template classic, sudah ada di seluruh app (kontinuitas brand).                                    |
| Paper  | `#f8fafc` | Background utama                                    | Neuter, tidak kompetisi dengan accent.                                                                                            |
| Navy   | `#1e40af` | Primary accent UI: CTA utama, badge, link aktif app | Warna h2 template modern, identitas app yang sudah dibangun (R-07 kolinieritas). Bukan warna link di dalam dokumen CV (lihat §7). |
| Powder | `#b0e0e6` | Accent kedua: highlight, dekorasi background        | User pilih (jawaban free text). Tidak bersaing dengan navy (R-25), tint dingin yang cocok dipasangkan dengan cool neutral.        |
| White  | `#ffffff` | Surface card, input                                 | Netral untuk konten dense.                                                                                                        |
| Error  | `#dc2626` | Alert error                                         | Konvensi darurat.                                                                                                                 |

- Powder `#b0e0e6` untuk teks di atas paper kontrasnya 1.43:1, gagal AA. Selalu pasangkan dengan ink.
- Dark mode: background `#27272a` (zinc-800 abu medium Opsi A), surface `#3f3f46` (zinc-700 terangkat), teks `#f8fafc`, navy `#3b82f6` (lebih terang agar kontras), powder `#b0e0e6` tetap. Border `#f4f4f5` (zinc-100) agar terlihat di abu, shadow `#18181b` (zinc-900) tetap terbaca di atas surface abu. Latar gelap bukan hitam murni (alexmayhew.dev).
- CTA utama harus navy. Powder hanya untuk highlight dan dekorasi.
- Navy tidak dipakai sebagai warna teks di dalam dokumen CV. Dokumen CV hanya memakai 2 netral (ink + abu neutral) + 1 aksen (navy/mint hanya untuk border heading section, R-29). Link di dalam dokumen CV memakai ink netral (lihat §7).

## 4. Tipografi

### Tipografi Antarmuka Aplikasi (UI)

- Font stack UI: Tailwind default (system sans: `ui-sans-serif, system-ui, sans-serif`).
- Heading UI: weight `font-black` (900) untuk hierarki layar. Heading dokumen CV lebih ringan (`font-bold` untuk nama/h1, `font-extrabold` untuk heading section/h2) karena kertas tidak butuh bobot UI (R-07).
- Body: weight `font-normal`, teks sekunder `text-ink/80` di light mode, max 12 kata per baris di landing.
- Angka besar di landing: `font-black tabular-nums`.

### Tipografi Dokumen CV (Kustomisasi Font & Ukuran)

Dokumen CV mendukung pemilihan jenis font dan ukuran teks terkurasi untuk fleksibilitas keterbacaan serta optimasi batas halaman (A4 ATS-friendly):

1. **Pilihan Jenis Font (`fontFamily`)**
   - **Bawaan Template (`default`)**: Menggunakan font default bawaan template (`font-serif` untuk Classic, `font-sans` untuk Modern & Neon).
   - **Inter (`inter`)**: Modern Sans, bersih, netral, sangat optimal untuk tech dan startup.
   - **Source Sans 3 (`source-sans`)**: Corporate Sans, keterbacaan tinggi, standar korporat & institusi.
   - **Source Serif 4 (`source-serif`)**: Formal Serif, netral dan tegas, cocok untuk dokumen korporat dan teknis.
   - **Lora (`lora`)**: Formal Serif kontemporer, elegan, cocok untuk akademisi, hukum, dan manajemen.
   - **Merriweather (`merriweather`)**: Classic Editorial Serif, kokoh dan berbobot untuk posisi senior dan industri kreatif.
   - _Mekanisme Web Font_: Font non-default dimuat via Google Fonts CDN (`<Teleport to="head">` di `CvPreview.vue`). Penghitungan tinggi halaman otomatis menunggu `document.fonts.ready` sebelum paginasi dijalankan.

2. **Pilihan Skala Ukuran Teks (`fontSize`)**
   - Semua ukuran font dokumen CV memakai satuan **pt** (bukan px) supaya preview layar dan cetak PDF identik. Nilai default: **nama/h1 24pt, heading section/h2 11pt, body 10pt, meta 9pt**.
   - **Kompak (`compact`)**: h1 21pt, h2 10pt, body 9pt, meta 8pt. Berguna untuk memadatkan isi CV agar pas dalam 1 atau 2 halaman utuh tanpa memotong teks.
   - **Standar (`default`)**: h1 24pt, h2 11pt, body 10pt, meta 9pt. Rasio seimbang standar industri.
   - **Lega (`spacious`)**: h1 27pt, h2 12pt, body 11pt, meta 10pt. Cocok untuk profil ringkas dengan pengalaman terpilih agar mengisi halaman secara proporsional.
   - _Mekanisme CSS_: Menggunakan kelas kontainer `.cv-size-compact` dan `.cv-size-spacious` di `main.css` dengan aturan spesifisitas tinggi (`!important`) yang menargetkan kelas `text-[24pt]` (h1), `text-[11pt]` (h2), `text-[10pt]` (body), dan `text-[9pt]` (meta) Tailwind v4 secara deterministik.

3. **Sinkronisasi Single-Source Preview & PDF**
   - Nilai `fontFamily` dan `fontSize` disimpan dalam struktur JSON `data` CV, divalidasi oleh `StoreCvRequest.php`, dan diinjeksikan langsung ke `print.html` melalui `print-main.ts`.
   - Browser rendering di Browsershot menguji `document.fonts.ready` sebelum mengambil snapshot PDF, memastikan hasil cetak PDF 100% identik dengan apa yang dilihat pengguna di preview kanvas.

4. **Ketajaman Teks PDF (print hint, 2026-09-18)**
   - Teks dokumen PDF **vektor asli** (Chromium `Page.printToPDF`), bukan rasterisasi gambar: 100% selectable, copy-pasteable, dan ter-parse ATS. Keseluruhan prinsip ini **tidak boleh** dikorbankan demi ketajaman (jangan pernah render dokumen jadi gambar 300 DPI).
   - Blok `@media print` di `CvPreview.vue` mengembalikan subpixel AA bawaan (`-webkit-font-smoothing: auto`, `-moz-osx-font-smoothing: auto`) dan menambah `text-rendering: geometricPrecision` pada `.cv-page`/`.a4-page-inner`. Hint `antialiased` (grayscale AA) memang di-set untuk tampilan layar, tetapi bila terbawa ke render print membuat glyph tampak lebih tipis/halus. Perubahan ini **hanya** berlaku di media print, jadi preview editor di layar tidak berubah.
   - **Blur saat membuka PDF di browser (mis. Edge) yang makin terasa saat di-zoom-out adalah artefak viewer, bukan cacat file.** Viewer menggambar halaman ke `<canvas>` lalu memakai CSS transform untuk memetakan ke piksel layar; pada `devicePixelRatio` ≠ 1 atau zoom < 100% terjadi interpolasi sehingga terlihat blur (sumber: dokumentasi PDF.js/Mozilla Bugzilla, utas resmi Microsoft Edge PDF blurry, dan panduan PDF.js Express "blur when zoomed out"). Uji cepat yang membedakan artefak viewer dari cacat file: teks bisa diseleksi & di-Ctrl+F, dan zoom 150%+ membuat teks kembali tajam. Untuk membandingkan secara adil, buka file yang sama di Adobe Reader atau Firefox.

## 5. Copy

- Bahasa Indonesia santai profesional. Zero em dash (R-02).
- CTA spesifik, bukan generik (R-15): tombol utama "Buat CV pertama", CTA akhir "Coba sekarang, gratis".
- Tanpa fake stats, fake testimonial, atau klaim tidak verifikasi (R-17, R-18, R-36). Landing
  ResumeKan hanya memuat fitur yang benar-benar ada: 3 template ATS (modern, classic, neon),
  simpan draft, ringkasan AI, download PDF A4.
- Satu nilai utama per section, max 2 properti per card (R-11).

## 6. Motion (MOTION 1)

- Hover tombol: translate-y 2px + shadow mengecil (interaksi nyata, Slab reference).
- Scroll-reveal: fade + translate-y 12px, delay bertingkat max 60ms per card.
- Semua animasi hormati `prefers-reduced-motion` (R-12, Slab reference).
- Tanpa loop, tanpa parallax, tanpa smooth scroll.

## 7. Hero

- Mock browser window (border ink 2px, hard shadow `6px` terang di dark `#f4f4f5`, title bar ink dengan 3 dot) berisi preview CV asli dari komponen `CvPreview` yang dipakai di editor. Bukan ilustrasi, bukan screenshot palsu (C-5).
- Struktur entry Experience di preview (semua template): baris 1 `Posisi · Perusahaan` + tanggal kanan, baris 2 metadata `Employment Type · Lokasi`, lalu bullets. Metadata tidak campur title line (konsisten pola Education).
- Struktur entry Organisasi di preview (semua template): baris 1 `Nama Organisasi` (bold) + periode kanan, baris 2 `Peran:` label ink semibold + nilai body, lalu bullets. Organisasi sebagai entitas utama di baris 1, peran sebagai detail di baris 2, kontras dengan pola Experience (peran di atas) karena organisasi lebih penting untuk identitas.
- Struktur entry Proyek di preview (semua template): baris 1 `Judul Proyek` (bold), baris 2 `Objective` (body), baris 3 `Peran:` label ink semibold + nilai body, dipisah `·`, lalu `Tech Stack:` label ink semibold + nilai body dalam baris yang sama. Hierarki: title sebagai entitas, objective sebagai deskripsi, role + tech stack sebagai metadata berlabel satu baris (Exa ATScore: name → description → tools; TMJ Studio: stack di akhir). Metadata sengaja **tidak** pakai bullet: marker bullet menempel tepat sebelum label field sehingga parser ATS berisiko menggabungkannya ke narasi deskripsi; satu baris berlabel tanpa marker menjaga batas field tetap eksplisit (revisi 2026-09-13).
- Struktur entry Sertifikasi di preview (semua template): baris 1 `Nama Sertifikat` + `by Penerbit` (label `by` ink semibold, nama penerbit body), baris 2 `ID:` label ink semibold + nilai body. Tahun tetap tier meta (kwartener, tanggal bukan pembeda).

### Hierarki Tipografi Teks CV (revisi 2026-09-18 — Pure Monochrome Ink)

Empat tier, dari paling kuat ke paling lemah. Angka kontras diukur di atas putih (WCAG 2.1):

| Tier | Peran                     | Kelas Tailwind                                                                               | Hex terukur (modern / classic / neon) | Kontras                          |
| ---- | ------------------------- | -------------------------------------------------------------------------------------------- | ------------------------------------- | -------------------------------- |
| T1   | Entitas + label           | `font-bold text-neutral-950` (classic: `font-bold text-black`, neon: `font-bold text-black`) | `#0a0a0a` / `#000000` / `#000000`     | 19.80:1 / 21.00:1 / 21.00:1      |
| T2   | Isi / nilai / deskripsi   | `text-neutral-950` (classic: `text-black`, neon: `text-black`)                               | `#0a0a0a` / `#000000` / `#000000`     | 19.80:1 / 21.00:1 / 21.00:1      |
| T3   | Metadata sekunder         | `text-neutral-800` (neon: `text-black`)                                                      | `#262626` / `#262626` / `#000000`     | 15.13:1 / 15.13:1 / 21.00:1      |
| T4   | Dekoratif (pemisah/garis) | `text-neutral-400` (neon: `decoration-[#6b7280]`)                                            | `#a1a1a1` / `#a1a1a1` / `#a1a1a1`     | 2.58:1 (pemisah `·` & underline) |

- Alasan revisi 2026-09-18 (Pure Monochrome Ink): isi deskripsi/bullet, ringkasan, dan teks body dinaikkan dari `slate-700` ke ink (`text-neutral-950`, Neon `#000`) agar teks di preview dan cetak/download PDF hitam pekat maksimal, mengikuti standar resume ATS dan cetak laser. Pembeda T1 dan T2 kini murni berbasis font weight (`font-bold` vs `font-normal`), konsisten dengan standar resume Harvard.
- Revisi 2026-10-02 (neutral near-black, commit `f7191a7`): seluruh ink dokumen CV pindah dari palet `slate-*` (biru-abu, mis. `#0f172b`) ke `neutral-*` (near-black, mis. `#0a0a0a`) supaya warna terlihat lebih hitam pekat dan netral di layar maupun cetak. Neon memakai `#000` murni.
- Revisi 2026-10-03 (Classic hitam pekat): T1/T2 template **classic** dinaikkan dari `#0a0a0a` ke `#000` murni supaya benar-benar pekat seperti neon. Warna ditulis **literal di class template** (`CvClassic.vue` memakai `text-black`/`border-black`), bukan menimpa CSS: komponen bersama (`PreviewSection`, `EntryRow`, `BulletList`) menerima prop `inkClass`/`ruleClass` dengan default `text-neutral-950`/`border-neutral-950`, sehingga **modern tetap `#0a0a0a`** dan neon tetap `#000` tanpa aturan override. T3 (`text-neutral-800`, periode/tahun) dan T4 (pemisah `·`/underline `text-neutral-400`) classic sengaja **tidak** diubah agar hierarki T3/T4 tetap ada; hanya T1/T2 yang jadi `#000`. Verifikasi browser: classic T1/T2 `rgb(0,0,0)`, modern tetap `#0a0a0a` + border navy `#1e40af`, neon `#000` + divider mint `#6ee7b7`, media `screen` == `print`.
- Revisi 2026-10-03 (Neon diseragamkan): `CvNeon.vue` sebelumnya menulis ink sebagai arbitrary value `text-[#000]`/`decoration-[#000]` dan lupa mengoper `inkClass`/`markerClass` ke `BulletList`, sehingga bullet jatuh ke default `text-neutral-950` (`#0a0a0a`) dan marker `marker:text-neutral-800` (`#262626`) — bocor near-black di tengah dokumen yang seharusnya `#000`. Kini seluruh ink Neon memakai literal `text-black`/`decoration-black` (identik dengan Classic) dan setiap `BulletList` dioper `ink-class="text-black" marker-class="marker:text-black"`. Divider mint `#6ee7b7` tetap aksen, bukan ink. `CvTemplateConfig` juga dibersihkan: 10 field mati (`badge`, `headerAlign`, `headerMargin`, `h1Class`, `linkClass`, `otherMode`, `layout`, `accent`, `hasBorder`, `hasQr`) dihapus, tersisa `id`, `label`, `atsFriendly`, `font`, `googleFamily`, `nameUppercase`.
- Metadata sekunder (T3, periode & tanggal) kini `text-neutral-800` (`#262626`, 15.13:1) agar tetap lebih ringan dari body tanpa pudar saat dicetak di printer monokrom.
- Elemen dekoratif T4 (pemisah `·` dan garis underline istirahat) kini `text-neutral-400` (`#a1a1a1`, 2.58:1) agar lebih kontras dan tegas tanpa mendominasi teks.
- Semua teks terbaca jauh melampaui WCAG AA (minimum 4.5:1), dengan body text mencapai 19.80:1.

### Warna Link di Dalam Dokumen CV

Link tidak memakai warna aksen. Aturannya (revisi 2026-09-18):

| Template | Warna teks                   | Underline (istirahat)    | Underline (hover)        | Kontras |
| -------- | ---------------------------- | ------------------------ | ------------------------ | ------- |
| modern   | `text-neutral-950` `#0a0a0a` | `decoration-neutral-400` | `decoration-neutral-950` | 19.80:1 |
| classic  | `text-black` `#000000`       | `decoration-neutral-400` | `decoration-black`       | 21.00:1 |
| neon     | `text-black` `#000000`       | `decoration-[#6b7280]`   | `decoration-black`       | 21.00:1 |

- Akar masalah (ditemukan 2026-09-13 lewat pengukuran PDF asli): `CvPreview.vue` punya `@media print { a { color: inherit !important; text-decoration: none !important } }`. Aturan `!important` itu **mengalahkan setiap class warna Tailwind** pada `<a>`, apa pun yang ditulis di template. Di Browsershot (yang merender dalam print media) link jatuh ke `inherit` = warna parent (`<p class="text-slate-600">`), jadi link di PDF selalu `#45556c` (slate-600), bukan ink, dan underline-nya hilang. Akibatnya preview di layar dan PDF memang beda persis di link, dan R5 pertama (mengubah class di template) tidak mengubah PDF sama sekali.
- Perbaikan: aturan print dipersempit ke `a:not([class])` dan `!important` dicabut. Link tanpa class (mis. tautan di dalam teks yang ditulis user) tetap dinetralkan supaya biru default browser tidak ikut tercetak; link template CV, yang semuanya punya class warna sendiri, kini benar-benar menerapkan warnanya di PDF. Alternatif menghapus aturan print sepenuhnya ditolak karena `<a>` berwarna di dalam konten user akan ikut tercetak biru.
- Konsekuensi: palet dokumen menyusut ke 2 netral + 1 aksen border (R-29), navy hanya dipakai untuk border heading section modern, bukan teks.
- Underline tetap `underline` permanen (bukan `hover:underline` saja) supaya link tetap terbaca sebagai link tanpa warna. Di atas kertas, underline adalah satu-satunya sinyal link yang bertahan (WCAG 1.4.1: jangan pakai warna sebagai satu-satunya pembeda).
- Underline istirahat memakai `decoration-neutral-400` (`#a1a1a1`) / Neon `decoration-[#6b7280]` (`#6b7280`) agar lebih kontras dari sebelumnya; hover menguat ke ink.
- Verifikasi lewat PDF asli (bukan simulasi): warna teks link di PDF terukur `#0a0a0a` (modern) dan `#000000` (classic, neon), dengan garis underline benar-benar ada sebagai vector di PDF. Diukur juga di browser: media `screen` dan `print` menghasilkan warna dan underline identik, jadi preview = PDF.

### Warna Data Pribadi di Header (revisi 2026-09-18)

Baris kontak di header tidak lagi dua tonjolan. Data pribadi non-link (email, telepon, alamat) naik dari slate-600 ke **ink**, sama dengan link:

| Elemen                            | modern                       | classic                 | neon      | Underline |
| --------------------------------- | ---------------------------- | ----------------------- | --------- | --------- |
| email, telepon, alamat            | `text-neutral-950` `#0a0a0a` | `text-black` `#000000`  | `#000000` | tidak     |
| LinkedIn, Website, GitHub (`<a>`) | `text-neutral-950` `#0a0a0a` | `text-black` `#000000`  | `#000000` | ya        |
| pemisah `·`                       | `text-neutral-400` (T4)      | `text-neutral-400` (T4) | n/a       | tidak     |

- Alasan: sebelumnya email/telepon/alamat dibaca lebih lemah dari link di sebelahnya (`slate-600` = `#45556c`, 7.58:1 vs ink 17.83:1) padahal keduanya sama-sama cara recruiter menghubungi kandidat. Beda tonjolan di baris yang sama membuat link terlihat lebih penting daripada nomor telepon, padahal tidak.
- Underline **tidak** diberikan ke email/telepon/alamat. Bukan link, jadi underline akan jadi janji palsu (recruiter mengira bisa diklik). Warna saja sudah menyamakan bobot visual; underline tetap eksklusif penanda link (WCAG 1.4.1, dan konsisten dengan aturan link di atas).
- Catatan Neon: `mailto:` dan `tel:` di Neon memang dirender sebagai `<a>` (bisa diklik di PDF), jadi keduanya ber-underline. Satu-satunya item non-link di Neon adalah alamat, dan itu kini ikut ink `#000000`.
- Pemisah `·` sengaja tetap T4 (slate-300/#cad5e2): ia murni dekoratif, dan menaikkannya ke ink akan membuat baris kontak terbaca sebagai satu blok teks rapat tanpa jeda.
- Baris placeholder `email · phone · address` saat seluruh kontak kosong juga tidak diubah (tetap `text-neutral-800`): itu teks contoh, bukan data user, jadi justru tepat kalau lebih redup.
- Verifikasi lewat PDF asli: email/telepon di PDF terukur `#0a0a0a` (modern) dan `#000000` (classic), sedangkan alamat di Neon `#000000`, identik dengan link di dokumen yang sama; di browser media `screen` dan `print` menghasilkan warna yang sama, jadi preview = PDF.
- Di atas preview: toggle Modern / Classic / Neon yang mengubah template preview live (bukti fitur template). Tombol Modern dan Classic dilengkapi badge mini "ATS" berbasis properti `atsFriendly`.
- Toggle preview full render, bukan gambar. Ini juga membuktikan template asli, bukan mock.
- Tanpa badge/eyebrow pill di atas headline (AI slop — pill badge, Exa pols.dev/slop.md, antislop-ui). Headline langsung tanpa `mt-4` kompensasi.
- Spacing hero `py-10 lg:py-14` + teks `lg:-translate-y-12` (naik, CTA di atas fold) — bukan `py-16 lg:py-24` simetris. Preview `h-[520px] @[520px]:h-[540px] p-0` + `scale-[0.72] @[520px]:scale-[0.85] origin-top` tanpa scroll, margin kanan-kiri maksimal, simetris dengan kolom kiri. Border `1.5px`/`2px` shadow `4px`/`6px` zinc-950.

## 8. Rhythm Landing

| Bagian    | Background       | Isi                                         |
| --------- | ---------------- | ------------------------------------------- |
| Hero      | paper + dot grid | Judul + CTA + mock browser dengan CvPreview |
| Fitur     | ink              | 4 card fitur, teks putih, ikon garis        |
| FAQ       | powder/40 + dots | 6 Q accordion, jawaban umum, CTA di Q3      |
| CTA akhir | navy             | Tombol "Coba sekarang, gratis"              |

Variasi background mencegah modul identik beruntun (R-08, RHYTHM 2).

Section CTA akhir memakai `bg-navy` **tanpa** `dark:` variant (audit kontras 2026-10-01). Sebelumnya
ada `dark:bg-main` yang mengubah latar ke `#2563eb`; di latar itu `text-white/80` hanya 3.89:1 dan
gagal ambang 4.5:1 untuk teks 16px normal. Navy `#1e40af` dipakai di kedua mode sehingga hasilnya
konsisten: judul putih 8.72:1, subteks `text-white/80` 6.19:1, tombol 17.06:1 (light) / 9.98:1 (dark).
Pelajaran: `dark:` variant pada **background section** harus diikuti pengukuran ulang kontras seluruh
isinya, karena menggelapkan/mencerahkan latar menggeser rasio semua teks di atasnya.

## 9. Component Library

Paket `neobrutalism-vue` (registry neobrutalism-vue.com, berbasis shadcn-vue dan Reka UI, Tailwind v4,
WAI-ARIA). Instal via `pnpm dlx shadcn-vue@latest add https://neobrutalism-vue.com/r/<component>.json`.
Komponen hasil install berupa source code di `web/src/components/ui`, langsung disesuaikan dengan
palet Ink & Navy. Komponen yang diperlukan untuk Fase 6: button, card, badge.

## 10. Dark Mode

- Strategy: class-based. Tailwind v4: `@custom-variant dark (&:where(.dark, .dark *));` di `main.css`, class `.dark` di `<html>`. FOUC guard inline script di `index.html` head (hanya `localStorage resumekan-theme === "dark"`, tanpa `prefers-color-scheme`).
- Toggle 2-way di navbar: `light ↔ dark` (`useDarkMode.ts` — `choice` ref `light|dark`, `isDark()` function, `cycle()`, `colorScheme` sync). Default `light` untuk semua user (tanpa auto/device). Pilihan disimpan `localStorage`. Ikon `Moon` (ke dark) / `Sun` (ke light).
- Token dark di `main.css`: `--background #27272a` (zinc-800), `--secondary-background #3f3f46` (zinc-700), `--foreground #f8fafc`, `--main #3b82f6`, `--border #f4f4f5`, `--shadow #18181b` (zinc-900), `color-scheme: dark`.
- Cakupan: semua halaman dark-mode (landing, navbar, footer, login, register, dashboard, CV form). `CvPreview` dan template PDF tetap putih (dokumen kertas). `CvForm.vue` pakai CSS dark non-scoped bernamespace `.cv-form` untuk 40+ field (input/select/textarea/label/h2/p) agar tidak duplikasi `dark:` per-field. Elemen non-field (tombol `+ Tambah`, label `#N`, `Hapus`, card section, stepper nav) pakai `dark:` variant dengan token (`foreground/70`, `foreground/60`, `red-300`, `border`) — kontras di atas surface zinc-700 minimal AA (audit 2026-08-30: sebelumnya slate-700/slate-500/red-600 kontras 1.01-2.19:1, gagal). Hover states disinkronkan kedua mode: light `hover:bg-slate-200 hover:text-slate-700`, dark `dark:hover:bg-white/15 dark:hover:text-foreground`.
- Teks sekunder di landing dan Dashboard (audit kontras 2026-10-01): opacity `text-ink/50`
  (3.39:1) dan `text-ink/55` (3.95:1) gagal AA di atas paper `#f8fafc`, dinaikkan ke tier yang
  aman: `text-ink/75` (7.74:1) untuk eyebrow/label, `text-ink/80` (9.29:1) untuk paragraf
  sekunder dan jawaban FAQ, `text-ink/70` (6.50:1) untuk ikon ChevronDown. `disabled:text-ink/70`
  → `disabled:text-ink/80`. Nilai terukur di atas kertas: `/60` = 4.62:1 (lolos tipis),
  `/65` = 5.49:1, `/70` = 6.50:1, `/75` = 7.74:1, `/80` = 9.29:1. Aturan praktis: untuk teks
  sekunder di light mode jangan turun di bawah `/75` agar ada margin di atas 4.5:1.
  Cakupan edit: `HomeView`, `DashboardView`, `AppNavbar`, `AppFooter`, `LoginView`, `RegisterView`
  (15 nilai). Varian `dark:` tidak diubah karena sudah lolos: `foreground/60` = 6.10:1 di
  `#27272a`, dan `foreground/75` = 6.44:1 di surface `#3f3f46`.
- Teks sekunder dark mode di landing pakai `dark:text-slate-300` (audit 2026-10-01, Opsi D),
  menggantikan `dark:text-foreground/60` dan `/70` di `HomeView`, `AppNavbar`, `AppFooter`
  (7 nilai). Alasan bukan kegagalan WCAG — `foreground/60` sudah 6.08:1 dan `/70` sudah 7.71:1,
  keduanya lolos AA. Alasan sebenarnya adalah **rendering**: `foreground/60` adalah
  `color-mix(in oklab, ...)` yang menghasilkan teks terang ber-alpha 0.6 di atas latar gelap,
  dan anti-aliasing membuat huruf tampak lebih tipis dan berkabut dibanding teks solid pada
  rasio yang sama. `slate-300` = `#cad5e2` (alpha 1.00, abu solid) menghapus `color-mix()` dari
  jalur teks: hero eyebrow/sub/FAQ sub/footer/navbar = 10.02:1 di `#27272a`, FAQ jawaban dan
  ikon chevron = 7.03:1 di surface `#3f3f46`. Ini menerapkan aturan yang sudah ada di baris
  199 (dark mode memakai token abu solid, bukan opacity rendah) ke landing, yang sebelumnya
  hanya dipatuhi di CV form.
  Catatan penting: `slate-300` **tidak** di-override oleh token dark proyek ini; nilai aslinya
  `#cad5e2` dipertahankan. Aturan 1.4.3 WCAG hanya mengatur rasio, tidak mengatur ketajaman
  rendering, jadi perubahan ini murni kualitas desain dan standar internal DESIGN.md di titik
  ini lebih tinggi dari WCAG.
- Link aksi di halaman auth pakai `dark:text-slate-300`, **bukan** `dark:text-main`
  (audit 2026-10-01). `dark:text-main` sebelumnya dipakai di `LoginView.vue` ("Daftar"),
  `RegisterView.vue` ("Masuk"), dan `HomeView.vue` (link "Buat CV pertama →" di jawaban FAQ).
  Rasio terukur hanya **2.02:1** di atas surface `#3f3f46` — gagal 1.4.3 (butuh 4.5:1).
  Akar masalahnya adalah **salah pakai token**: `--main` dark mode (`#2563eb`) didefinisikan
  sebagai warna **background** tombol (tempat `text-white` di atasnya dapat 5.17:1), bukan
  sebagai warna **teks di atas surface**. Tabel kandidat terukur di `#3f3f46`:
  `#2563eb` 2.02 · blue-500 2.84 · blue-400 4.11 · blue-300 5.79 · **`slate-300` 7.03** ·
  slate-200 8.47 · `foreground` 9.98. Dipilih `slate-300` karena konsisten dengan Opsi D di
  atas dan memberi ruang aman (bukan pas-pasan seperti blue-300 5.79). Di dark mode, identitas
  link **tidak** lagi dibawa oleh hue biru — semua biru yang cukup terang untuk lolos di
  surface gelap sudah kehilangan identitas brand; yang membedakan link adalah `font-bold` +
  `underline decoration-2` yang sudah ada. Light mode tidak berubah (`text-navy` `#1e40af`,
  diverifikasi lewat tombol toggle asli, bukan manipulasi class DOM).
- Hierarki teks hero landing (audit 2026-10-01, **Opsi A** setelah Opsi C dibatalkan):
  eyebrow, `h1`, dan sub awalnya memakai `slate-300` yang sama sehingga terbaca satu blok rata
  tanpa tangga visual, padahal rasio terukurnya sudah 10.02:1 dan lolos AA dengan lega.
  Pelajaran: **rasio kontras yang lolos tidak menjamin keterbacaan; hierarki adalah masalah
  terpisah.**
  Percobaan pertama (Opsi C) membuat tangga tajam 5.66 → 12.08 → 14.24 dengan eyebrow
  `slate-400`. **Ini dibatalkan** karena justru menciptakan inkonsistensi: eyebrow dan sub
  hanya beda 2px ukurannya (14px vs 16px) dan dibaca berurutan dengan jarak 12px, sehingga
  selisih kontras 6.4 poin terbaca sebagai dua blok dari dunia berbeda (eyebrow tampak
  "disabled"), bukan sebagai hierarki. Pelajaran tambahan: **hierarki rasio ala heading
  (redup → terang → tengah) hanya bekerja kalau ada perbedaan UKURAN yang besar; kalau ukuran
  elemen hampir sama, tangga rasio harus halus.**
  Hierarki final (dark, latar `#27272a`):
  | elemen | kelas dark | rasio | alpha |
  |---|---|---|---|
  | eyebrow (`text-sm`) | `dark:text-slate-300` | 10.02:1 | 1.00 |
  | sub (`mt-4 max-w-md text-base`) | `dark:text-slate-200` | 12.08:1 | 1.00 |
  | `h1` (`text-4xl/5xl font-black`) | `dark:text-foreground` | 14.24:1 | 1.00 |
  Tangga 10.02 → 12.08 → 14.24 (jarak antar tingkat ~2 poin) tetap membentuk hierarki tetapi
  halus, dan ketiganya nyaman dibaca. Ini meniru kualitas light mode yang selisihnya hanya
  1.6 poin (`ink/75` 7.73:1 → `ink/80` 9.35:1 → `h1` 17.06:1) dan sudah dinyatakan aman.
  **`dark:text-slate-400` (`#90a1b9`) dilarang di hero:** 5.66:1 di `#27272a`, dan di surface
  `#3f3f46` jatuh ke **3.97:1 (gagal AA)** — jangan pakai token ini untuk teks di kartu/panel.
  Catatan lebar baris: sub hanya selebar **404px** sehingga selalu muat 1 baris di `max-w-md`
  (448px). Ambang satu barisnya ada di **416px**; `max-w-sm` (384px) memecahnya jadi 2 baris
  timpang 357px + 43px ("lamar.") — widow parah. **Jangan perkecil ke `max-w-sm`.** Kesalahan
  awal sesi ini adalah mengira memperpendek baris akan memperbaiki keterbacaan; pengukuran
  `Range.getClientRects()` membuktikan sebaliknya, dan usulan itu dibatalkan.
- Metode pengukuran wajib (audit 2026-10-01): Tailwind v4 mengeluarkan `color-mix(in oklab, ...)`
  dan browser melaporkannya sebagai `oklab(L a b / alpha)`. Nilai `oklab()` **tidak boleh**
  di-parse dengan regex — komponen `L a b` di luar rentang 0-255 dan menghasilkan rasio palsu
  (pernah melaporkan 20 "kegagalan" dark mode yang seluruhnya artefak). Konversi harus lewat
  rasterisasi kanvas 1×1: `ctx.fillStyle = css; ctx.fillRect(...); ctx.getImageData(...)`.
  Dua jebakan lain: (1) matikan `transition`/`animation` sebelum membaca `getComputedStyle`
  setelah toggle tema, kalau tidak nilainya tertangkap di tengah transisi; (2) ukur elemen
  terhadap latar **miliknya sendiri** (tombol `bg-paper text-ink` di dalam section navy harus
  diukur ke latar tombol, bukan latar section).
- Sisa temuan belum diperbaiki (audit 2026-10-01, sengaja dibiarkan): border tombol CTA akhir
  `border-ink` `#0f172a` di atas navy = 2.05:1, di bawah ambang 3:1 grafis WCAG 1.4.11. Untuk
  section ber-latar navy, border tombol sebaiknya memakai warna terang agar batas komponen
  terbaca.
- Field dark-mode (audit 2026-09-01): field memakai `dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring`; placeholder dikontrol di satu tempat `.dark .cv-form input::placeholder` → `color-mix(in srgb, var(--foreground) 80%, transparent)` (5.21:1 di atas `#55555c`, sebelumnya 65% = 4.04:1). `FormLabel` span → `text-slate-700 dark:text-foreground/75` (6.44:1, sebelumnya slate-700 1.23:1 saat dipakai di luar `<label>`). Field text = `#f8fafc` 7.03:1, input bg efektif `#55555c` (color-mix 12% foreground).
- Foto profil (opsional, template Neon): thumbnail klikabel buka modal lightbox aksesibel (`role="dialog"`, `aria-modal`, `aria-label`, tutup via ✕/Esc/klik-luar). Tombol `Hapus foto` → `text-red-600 dark:text-red-300` (5.44:1, sebelumnya red-600 1.98:1); error upload → `text-red-600 dark:text-red-300`; teks petunjuk → `text-slate-500 dark:text-slate-400` (4.77:1). `label → div` agar klik area kosong tidak memicu delete (label meneruskan klik ke kontrol pertama).
- Warna error dark = `red-300` (`#ffa2a2`, 5.44:1 di atas kartu `#3f3f46`), bukan `red-400` (3.78:1, gagal AA untuk teks 11px). Berlaku untuk semua pesan error (inline, banner, toast) — audit 2026-09-15.
- Teks sekunder step form (audit 2026-09-18): label section `<h2>`, teks bantuan `<p>`, dan empty state memakai `text-slate-500 dark:text-slate-300` (`#cad5e2`, 7.03:1 di atas zinc-700; light `#62748e` 4.76:1 di atas putih). Sebelumnya `dark:text-slate-400` (`#90a1b9`, 3.97:1, gagal) dan `text-slate-400` light (2.63:1, gagal). Counter karakter (mis. `x/500`) dan badge `text-[10px]` ikut pola sama (`text-slate-500 dark:text-slate-300`). Step chip non-active dan indikator "Langkah N/10" naik dari `dark:text-foreground/60` (`#babbbd`, 3.52:1, gagal) ke `dark:text-foreground/75` (`#c9c9cb`, 5.44:1 lolos). Prinsip: untuk teks sekunder dark mode pakai token abu **solid** (slate-300), bukan color-mix opacity rendah (`foreground/60`) yang gagal kontras. Verifikasi sweep kedua mode: light 205 simpul (0 gagal nyata), dark 205 simpul (0 gagal).
- Aturan: CSS kustom non-scoped yang menimpa warna (mis. blok `.cv-form` di `CvForm.vue`) **wajib** dibungkus `@layer components`. CSS unlayered selalu menang atas utility Tailwind v4 (ber-layer) tanpa peduli specificity — pernah membuat semua pesan error dark berubah abu (bug 2026-09-15).
- Konsekuensi penting (audit kontras 2026-09-18): karena utility Tailwind **menang** atas aturan warna di `@layer components`, blok `.dark .cv-form h2/p/label>span` (color-mix) di `CvForm.vue` **kalah** dari utility `dark:text-*` yang di-set inline di komponen step. Jadi kontras teks sekunder diatur lewat utility `dark:text-slate-300`/`dark:text-foreground/75` di tiap komponen step, bukan lewat color-mix di blok `.cv-form`. Aturan color-mix lama hanya efektif sebagai fallback untuk elemen tanpa utility warna.
- CV form stepper: 10 langkah (Info, Pribadi, Ringkasan, Pengalaman, Pendidikan, Organisasi, Keahlian, Proyek, Sertifikat, Lainnya). Chip bernomur 3 state (active navy, completed ✓ emerald, upcoming muted). Klikable, `v-show` per section (state field persist). Prev/Next + "Langkah N/10" indicator. Simpan CV di step terakhir. Inspirasi: FlowCV wizard, Rezi UX audit (Exa: progress indicator + guided navigation).
- Kontras teks di kedua mode minimal AA (R-25). Tidak pakai warna yang sama untuk text dan background di dark mode (R-34).

## 11. Dashboard (halaman daftar CV)

Direvisi 2026-09-20. Referensi arah: Dribbble "CVMaker Dashboard Home"
(shot 20640700, Balkan Brothers / BB Agency), dipakai sebagai inspirasi, bukan template
(R-30). Inti yang diambil: dokumen CV ditampilkan sebagai objek utama, aksi buat baru
selalu tersedia, dan sapaan memakai nama pengguna.

### Struktur

| Baris     | Isi                                                                                                    | Alasan                                                                                                                                                                                  |
| --------- | ------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Identitas | Avatar inisial, nama, email, tanggal bergabung, CTA `Buat CV baru`                                     | Referensi menyapa pengguna dengan nama. Semua isinya data pengguna sendiri, bukan aset karangan (R-23).                                                                                 |
| Kapasitas | Tiga kartu: Total CV, Siap diunduh, Batas akun (`n/10`)                                                | Angka nyata dihitung dari `cvStore.list`; `Siap diunduh` memakai `is_complete` dari server. Tidak ada delta atau tren karangan (R-17, R-38). Baris ini disembunyikan saat belum ada CV. |
| Daftar CV | Satu kartu per CV: thumbnail A4, judul, tag template, tag `Belum lengkap`, satu baris meta, baris aksi | Tiap kartu fokus ke satu keputusan: lanjutkan mengedit CV yang mana (C-3).                                                                                                              |

### Kartu CV

- Thumbnail `CvThumb` = dokumen A4 asli (794x1123) digambar 230x326 lalu di-`scale-[0.348]`
  ke dalam bingkai tetap 79x112 px. Kepala dokumen mengikuti warna template (navy Modern,
  ink Neon, slate-600 Classic) supaya identitas template terbaca tanpa menambah warna ke palet UI.
  Ini preview nyata, bukan screenshot palsu (C-5).
- Satu aksi utama per kartu: `Edit` navy (satu primer per kartu menjaga titik fokus, dan navy
  tidak tersebar, R-29). Sisanya icon-only 36x36 px (`Unduh PDF`, `Hapus`) dengan `aria-label`
  - `title` (R-32), plus satu tombol **berlabel teks** untuk aksi terjemah.
- **Satu ikon, satu arti (R-26).** Ikon tidak boleh dipakai ulang untuk dua verb berbeda di satu
  area produk. Tombol `Duplikat CV` yang dulu memakai ikon `Copy` sudah **dihapus**: isinya hanya
  `router.push('/cvs/:id/edit')`, jadi tidak menduplikasi apa pun, dan ikon `copy` di `lucide`
  berarti "salin ke clipboard", bukan duplikasi dokumen.
- Aksi terjemah memakai **teks terlihat** (`Terjemah EN` + ikon `Languages`), bukan icon-only +
  tooltip. Alasannya: aplikasi ini bilingual, ada dua verb yang berdekatan di baris aksi yang sama,
  dan pembaca targetnya non-native English; teks terlihat menghilangkan ambiguitas (R-32).
- Tombol terjemah **hanya muncul di kartu `language === 'id'`** (terjemah id -> en). Kartu `en`
  tidak punya tombol ini, sehingga kartunya jujur berisi satu aksi lebih sedikit (bukan bug).
  Karena tidak ada endpoint duplikat generik di API, tidak ada aksi tiruan untuk kartu `en`.
- Aksi dinonaktifkan selama ada alasan yang bisa dijelaskan, dengan `title` yang menyebut
  sebabnya (R-27). Tombol terjemah punya tiga alasan, dan urutan prioritasnya penting:
  (1) data belum lengkap, (2) `atLimit` (terjemah memakai kuota 10 CV), (3) sedang berjalan.
  Alasan "belum lengkap" didahulukan karena itu yang paling sering membingungkan pengguna.
- Kontras teks label tombol terjemah saat nonaktif dinaikkan ke `text-ink/70` (light) dan
  `text-foreground/75` (dark): terukur 6.67:1 dan 6.44:1. Teks nonaktif memang dikecualikan
  WCAG 1.4.3, tapi label inilah satu-satunya tempat yang menjelaskan kenapa aksinya mati,
  jadi harus benar-benar terbaca. Tombol ikon (PDF) tetap `ink/40` karena tidak membawa teks explica.
- State pending memakai label semantik `Menerjemahkan...` + spinner, dan `<ul>` diberi
  `aria-busy="true"` selama proses.

### Kontrak pesan error

- Pesan mentah Laravel **tidak pernah** ditampilkan apa adanya. `StoreCvRequest` hanya punya
  `messages()`/`attributes()` untuk field top-level, sehingga `data.certificates.*.issuer`
  jatuh ke teks Inggris default ("The data.certificates.0.issuer field is required when
  data.certificates is present"). Sekarang field entri berulang punya pesan + label sendiri,
  sehingga hasilnya "Penerbit (Sertifikat #1) wajib diisi."
- Di sisi klien, dua mapper terpisah mengikuti dua sumber kegagalan yang berbeda:
  - `translateError()` untuk `POST /cvs/{id}/translate`. Endpoint ini tidak memvalidasi `data.*`,
    jadi yang mungkin hanya 502 (layanan), 429 (throttle), dan 403 (bukan pemilik).
  - `createError()` untuk simpan CV terjemahan. **Di sinilah satu-satunya 422 berasal.** Kunci
    error dibaca untuk menyebut bagian yang kurang, mis. "CV \"X\" belum lengkap, jadi belum bisa
    disimpan sebagai CV baru. Lengkapi dulu bagian Sertifikat."
  - Keduanya dipisah karena 422 kuota (`errors.title`) dan 422 validasi (`data.*`) punya bentuk kunci
    berbeda. Tanpa pemisahan ini, kegagalan simpan mudah salah dilaporkan sebagai kegagalan terjemah.
- Ada **dua lapis**: tombolnya sudah `disabled` + `title` penjelas, dan handler-nya berpagar
  sehingga payload tidak pernah dikirim ke server. Terverifikasi: memaksa klik saat nonaktif
  tidak menghasilkan request `/translate` sama sekali dan hanya memunculkan pesan yang jelas.
- Aksi destruktif tetap memakai `confirm()`. Disabled state pada PDF memakai `title` yang
  menjelaskan sebabnya, bukan hanya meredupkan tombol (R-27).
- **Pending pada tombol PDF (2026-10-02):** karena render Chromium bisa 5-15 dtk, tombol
  mengubah ikonnya saat berjalan. Tombol ikon di Dashboard: `Download` → `Loader2` berputar,
  `:disabled` (hanya satu render PDF dalam satu waktu), `title`/`aria-label` → `Menyiapkan PDF...`,
  `aria-busy="true"`. Tombol header editor (berlabel teks): `Download PDF` → `Menyiapkan PDF...`
  - spinner, `:disabled`, `aria-busy="true"` — sejajar dengan pola `Menerjemahkan...`. Keduanya
    punya timeout 60 dtk (`AbortController`) supaya tombol tidak berputar selamanya bila server
    menggantung. Toast tetap hanya untuk hasil akhir, bukan indikator tunggu.
- Baris aksi memakai `mt-auto` sehingga menempel ke dasar kartu apa pun panjang isinya.
- Hover: hard shadow naik dari 4px ke 6px (penanda elevasi, bukan default di semua elemen, R-12).

### Tema & gaya

- Kartu memakai `border-2 border-ink` + hard shadow + `rounded-base`, sesuai §2. Tidak ada
  `shadow-sm`, `border-slate-200`, `rounded-2xl`, atau `bg-slate-50` (pola SaaS generik).
- Tanpa emoji sebagai ikon (R-04). Ikon memakai set yang sudah ada di navbar (`lucide-vue-next`).
- Kontras diverifikasi lewat sweep terprogram di kedua mode: light 0 gagal, dark 0 gagal
  (R-25, R-34).

### State

- **Loading**: skeleton tiga kartu dengan `aria-busy="true"` dan `sr-only` "Memuat daftar CV",
  bentuknya menyamai kartu asli supaya tata letak tidak lompat saat data masuk.
- **Kosong**: menyebut apa yang akan muncul di daftar ini, lalu tiga langkah nyata produk
  (isi form, pilih template, unduh PDF A4) dan CTA `Buat CV pertama`. Tanpa emoji (R-04), tanpa
  ilustrasi generik (R-22).
- **Error**: banner `role="alert"` (implisit `aria-live="assertive"`, jadi **tidak** ditambah
  `aria-live` lagi agar tidak diumumkan dua kali) dengan border merah dan teks red-700 /
  dark red-300.
- **Sukses**: paragraf `role="status"` + `aria-live="polite"` berlatar `bg-powder` yang menyebut
  judul CV yang baru dibuat. Hanya satu live region yang aktif pada satu waktu.

### Yang dihapus

- Tombol `Logout` di header halaman (navbar sudah punya Logout + toggle tema), agar tidak ada
  dua pintu keluar di satu layar.
- Emoji 📄 pada empty state.
- Teks "Memuat..." polos sebagai pengganti state.

## 12. Accessibility & Delivery Gate

- Semua komponen WAI-ARIA (dijamin shadcn-vue/Reka UI). Fokus keyboard terlihat (outline ink 2px).
- Kontras teks AA di light dan dark mode (R-25). Verifikasi wajib di **kedua mode** dengan sweep terprogram (hitung rasio tiap node teks via kanvas `getImageData` + `getComputedStyle`, ambang 4.5:1 teks normal / 3:1 teks besar) — nilai di atas kertas tidak cukup karena background efektif berbeda per mode (putih vs zinc-700).
- Hover, focus, active, loading, disabled, error states lengkap di semua tombol (R-27).
- Jalankan Delivery Gate antislop sebelum commit: cek em dash, kontras, keyboard, states, run dan
  verify di browser.
