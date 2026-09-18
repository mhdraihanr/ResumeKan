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
- Navy tidak dipakai sebagai warna teks di dalam dokumen CV. Dokumen CV hanya memakai 2 netral (ink + abu slate) + 1 aksen (navy/mint hanya untuk border heading section, R-29). Link di dalam dokumen CV memakai ink netral (lihat §7).

## 4. Tipografi

### Tipografi Antarmuka Aplikasi (UI)

- Font stack UI: Tailwind default (system sans: `ui-sans-serif, system-ui, sans-serif`).
- Heading: weight `font-black` (900), sama seperti heading preview CV (kontinuitas, R-07).
- Body: weight `font-normal`, `text-slate-700` di light mode, max 12 kata per baris di landing.
- Angka besar di landing: `font-black tabular-nums`.

### Tipografi Dokumen CV (Kustomisasi Font & Ukuran)

Dokumen CV mendukung pemilihan jenis font dan ukuran teks terkurasi untuk fleksibilitas keterbacaan serta optimasi batas halaman (A4 ATS-friendly):

1. **Pilihan Jenis Font (`fontFamily`)**
   - **Bawaan Template (`default`)**: Menggunakan font default bawaan template (`font-serif` untuk Classic, `font-sans` untuk Modern & Neon).
   - **Inter (`inter`)**: Modern Sans, bersih, netral, sangat optimal untuk tech dan startup.
   - **Source Sans 3 (`source-sans`)**: Corporate Sans, keterbacaan tinggi, standar korporat & institusi.
   - **Lora (`lora`)**: Formal Serif kontemporer, elegan, cocok untuk akademisi, hukum, dan manajemen.
   - **Merriweather (`merriweather`)**: Classic Editorial Serif, kokoh dan berbobot untuk posisi senior dan industri kreatif.
   - _Mekanisme Web Font_: Font non-default dimuat via Google Fonts CDN (`<Teleport to="head">` di `CvPreview.vue`). Penghitungan tinggi halaman otomatis menunggu `document.fonts.ready` sebelum paginasi dijalankan.

2. **Pilihan Skala Ukuran Teks (`fontSize`)**
   - **Kompak (`compact`)**: Base 10pt (body 9pt, heading skala ~0.9x). Berguna untuk memadatkan isi CV agar pas dalam 1 atau 2 halaman utuh tanpa memotong teks.
   - **Standar (`default`)**: Base 11pt (body 10pt). Rasio seimbang standar industri.
   - **Lega (`spacious`)**: Base 12pt (body 11pt, heading skala ~1.1x). Cocok untuk profil ringkas dengan pengalaman terpilih agar mengisi halaman secara proporsional.
   - _Mekanisme CSS_: Menggunakan kelas kontainer `.cv-size-compact` dan `.cv-size-spacious` di `main.css` dengan aturan spesifisitas tinggi (`!important`) untuk menimpa kelas `text-[10pt]` / `text-[9pt]` Tailwind v4 secara deterministik.

3. **Sinkronisasi Single-Source Preview & PDF**
   - Nilai `fontFamily` dan `fontSize` disimpan dalam struktur JSON `data` CV, divalidasi oleh `StoreCvRequest.php`, dan diinjeksikan langsung ke `print.html` melalui `print-main.ts`.
   - Browser rendering di Browsershot menguji `document.fonts.ready` sebelum mengambil snapshot PDF, memastikan hasil cetak PDF 100% identik dengan apa yang dilihat pengguna di preview kanvas.

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

| Tier | Peran                     | Kelas Tailwind                                  | Hex terukur | Kontras                          |
| ---- | ------------------------- | ----------------------------------------------- | ----------- | -------------------------------- |
| T1   | Entitas + label           | `font-semibold text-slate-900` (Neon: `#111`)   | `#0f172b`   | 17.83:1                          |
| T2   | Isi / nilai / deskripsi   | `text-slate-900` (Neon: `#111`)                 | `#0f172b`   | 17.83:1                          |
| T3   | Metadata sekunder         | `text-slate-700` (Neon: `#111` / `#314158`)     | `#314158`   | 10.36:1                          |
| T4   | Dekoratif (pemisah/garis) | `text-slate-400` (Neon: `decoration-[#6b7280]`) | `#90a1b9`   | 2.56:1 (pemisah `·` & underline) |

- Alasan revisi 2026-09-18 (Pure Monochrome Ink): isi deskripsi/bullet, ringkasan, dan teks body dinaikkan dari `slate-700` (`#314158`) ke `text-slate-900` (`#0f172b`, Neon `#111`) agar teks di preview dan cetak/download PDF hitam pekat maksimal, mengikuti standar resume ATS dan cetak laser. Pembeda T1 dan T2 kini murni berbasis font weight (`font-semibold`/`font-bold` vs `font-normal`), konsisten dengan standar resume Harvard.
- Metadata sekunder (T3, periode & tanggal) dinaikkan dari `slate-500` (4.76:1) ke `text-slate-700` (10.36:1) agar tidak pudar/light abu saat dicetak di printer monokrom.
- Elemen dekoratif T4 (pemisah `·` dan garis underline istirahat) dinaikkan dari `slate-300` (1.49:1) ke `text-slate-400` / `decoration-slate-400` (2.56:1) agar lebih kontras dan tegas tanpa mendominasi teks.
- Semua teks terbaca jauh melampaui WCAG AA (minimum 4.5:1), dengan body text mencapai 17.83:1.

### Warna Link di Dalam Dokumen CV

Link tidak memakai warna aksen. Aturannya (revisi 2026-09-18):

| Template | Warna teks                 | Underline (istirahat)  | Underline (hover)      | Kontras |
| -------- | -------------------------- | ---------------------- | ---------------------- | ------- |
| modern   | `text-slate-900` `#0f172b` | `decoration-slate-400` | `decoration-slate-900` | 17.83:1 |
| classic  | `text-slate-900` `#0f172b` | `decoration-slate-400` | `decoration-slate-900` | 17.83:1 |
| neon     | `text-[#111]` `#111111`    | `decoration-[#6b7280]` | `decoration-[#111]`    | 18.88:1 |

- Akar masalah (ditemukan 2026-09-13 lewat pengukuran PDF asli): `CvPreview.vue` punya `@media print { a { color: inherit !important; text-decoration: none !important } }`. Aturan `!important` itu **mengalahkan setiap class warna Tailwind** pada `<a>`, apa pun yang ditulis di template. Di Browsershot (yang merender dalam print media) link jatuh ke `inherit` = warna parent (`<p class="text-slate-600">`), jadi link di PDF selalu `#45556c` (slate-600), bukan ink, dan underline-nya hilang. Akibatnya preview di layar dan PDF memang beda persis di link, dan R5 pertama (mengubah class di template) tidak mengubah PDF sama sekali.
- Perbaikan: aturan print dipersempit ke `a:not([class])` dan `!important` dicabut. Link tanpa class (mis. tautan di dalam teks yang ditulis user) tetap dinetralkan supaya biru default browser tidak ikut tercetak; link template CV, yang semuanya punya class warna sendiri, kini benar-benar menerapkan warnanya di PDF. Alternatif menghapus aturan print sepenuhnya ditolak karena `<a>` berwarna di dalam konten user akan ikut tercetak biru.
- Konsekuensi: palet dokumen menyusut ke 2 netral + 1 aksen border (R-29), navy hanya dipakai untuk border heading section modern, bukan teks.
- Underline tetap `underline` permanen (bukan `hover:underline` saja) supaya link tetap terbaca sebagai link tanpa warna. Di atas kertas, underline adalah satu-satunya sinyal link yang bertahan (WCAG 1.4.1: jangan pakai warna sebagai satu-satunya pembeda).
- Underline istirahat memakai `decoration-slate-400` (`#90a1b9`) / Neon `decoration-[#6b7280]` agar lebih kontras dari sebelumnya (`slate-300`); hover menguat ke ink.
- Verifikasi lewat PDF asli (bukan simulasi): warna teks link di PDF terukur `#0f172b` (modern, classic) dan `#111111` (neon), dengan garis underline benar-benar ada sebagai vector di PDF. Diukur juga di browser: media `screen` dan `print` menghasilkan warna dan underline identik, jadi preview = PDF.

### Warna Data Pribadi di Header (revisi 2026-09-18)

Baris kontak di header tidak lagi dua tonjolan. Data pribadi non-link (email, telepon, alamat) naik dari slate-600 ke **ink**, sama dengan link:

| Elemen                            | modern / classic           | neon      | Underline |
| --------------------------------- | -------------------------- | --------- | --------- |
| email, telepon, alamat            | `text-slate-900` `#0f172b` | `#111111` | tidak     |
| LinkedIn, Website, GitHub (`<a>`) | `text-slate-900` `#0f172b` | `#111111` | ya        |
| pemisah `·`                       | `text-slate-400` (T4)      | n/a       | tidak     |

- Alasan: sebelumnya email/telepon/alamat dibaca lebih lemah dari link di sebelahnya (`slate-600` = `#45556c`, 7.58:1 vs ink 17.83:1) padahal keduanya sama-sama cara recruiter menghubungi kandidat. Beda tonjolan di baris yang sama membuat link terlihat lebih penting daripada nomor telepon, padahal tidak.
- Underline **tidak** diberikan ke email/telepon/alamat. Bukan link, jadi underline akan jadi janji palsu (recruiter mengira bisa diklik). Warna saja sudah menyamakan bobot visual; underline tetap eksklusif penanda link (WCAG 1.4.1, dan konsisten dengan aturan link di atas).
- Catatan Neon: `mailto:` dan `tel:` di Neon memang dirender sebagai `<a>` (bisa diklik di PDF), jadi keduanya ber-underline. Satu-satunya item non-link di Neon adalah alamat, dan itu kini ikut ink `#111111`.
- Pemisah `·` sengaja tetap T4 (slate-300/#cad5e2): ia murni dekoratif, dan menaikkannya ke ink akan membuat baris kontak terbaca sebagai satu blok teks rapat tanpa jeda.
- Baris placeholder `email · phone · address` saat seluruh kontak kosong juga tidak diubah (tetap `slate-600`): itu teks contoh, bukan data user, jadi justru tepat kalau lebih redup.
- Verifikasi lewat PDF asli: email/telepon di PDF terukur `#0f172b` (modern, classic) dan alamat di Neon `#111111`, identik dengan link di dokumen yang sama; di browser media `screen` dan `print` menghasilkan `#0f172b` yang sama, jadi preview = PDF.
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
- Field dark-mode (audit 2026-09-01): field memakai `dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring`; placeholder dikontrol di satu tempat `.dark .cv-form input::placeholder` → `color-mix(in srgb, var(--foreground) 80%, transparent)` (5.21:1 di atas `#55555c`, sebelumnya 65% = 4.04:1). `FormLabel` span → `text-slate-700 dark:text-foreground/75` (6.44:1, sebelumnya slate-700 1.23:1 saat dipakai di luar `<label>`). Field text = `#f8fafc` 7.03:1, input bg efektif `#55555c` (color-mix 12% foreground).
- Foto profil (opsional, template Neon): thumbnail klikabel buka modal lightbox aksesibel (`role="dialog"`, `aria-modal`, `aria-label`, tutup via ✕/Esc/klik-luar). Tombol `Hapus foto` → `text-red-600 dark:text-red-300` (5.44:1, sebelumnya red-600 1.98:1); error upload → `text-red-600 dark:text-red-300`; teks petunjuk → `text-slate-500 dark:text-slate-400` (4.77:1). `label → div` agar klik area kosong tidak memicu delete (label meneruskan klik ke kontrol pertama).
- Warna error dark = `red-300` (`#ffa2a2`, 5.44:1 di atas kartu `#3f3f46`), bukan `red-400` (3.78:1, gagal AA untuk teks 11px). Berlaku untuk semua pesan error (inline, banner, toast) — audit 2026-09-15.
- Aturan: CSS kustom non-scoped yang menimpa warna (mis. blok `.cv-form` di `CvForm.vue`) **wajib** dibungkus `@layer components`. CSS unlayered selalu menang atas utility Tailwind v4 (ber-layer) tanpa peduli specificity — pernah membuat semua pesan error dark berubah abu (bug 2026-09-15).
- CV form stepper: 9 langkah (Info, Pribadi, Ringkasan, Pengalaman, Pendidikan, Organisasi, Keahlian, Proyek, Lainnya). Chip bernomur 3 state (active navy, completed ✓ emerald, upcoming muted). Klikable, `v-show` per section (state field persist). Prev/Next + "Langkah N/9" indicator. Simpan CV di step terakhir. Inspirasi: FlowCV wizard, Rezi UX audit (Exa: progress indicator + guided navigation).
- Kontras teks di kedua mode minimal AA (R-25). Tidak pakai warna yang sama untuk text dan background di dark mode (R-34).

## 11. Accessibility & Delivery Gate

- Semua komponen WAI-ARIA (dijamin shadcn-vue/Reka UI). Fokus keyboard terlihat (outline ink 2px).
- Kontras teks AA di light dan dark mode (R-25).
- Hover, focus, active, loading, disabled, error states lengkap di semua tombol (R-27).
- Jalankan Delivery Gate antislop sebelum commit: cek em dash, kontras, keyboard, states, run dan
  verify di browser.
