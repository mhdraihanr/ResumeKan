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

### Dilarang (antislop)

- Emoji, gradien, glow, purple/blue default Tailwind, glassmorphism (R-01, R-30).
- Em dash di copy (R-02). Gunakan koma, titik, atau kurung.
- Heading font kustom untuk gaya semata (R-06). Font stack default dengan weight ekstra.
- Paragraf panjang di landing. Max ~12 kata per baris.

## 3. Palet: Ink & Navy

> Pengganti amber yang awalnya diusulkan: user memilih powder blue sebagai accent kedua.

| Token  | Hex       | Fungsi                                       | Alasan                                                                                                                     |
| ------ | --------- | -------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Ink    | `#0f172a` | Border, teks utama, section gelap            | Warna teks default preview dan template classic, sudah ada di seluruh app (kontinuitas brand).                             |
| Paper  | `#f8fafc` | Background utama                             | Neuter, tidak kompetisi dengan accent.                                                                                     |
| Navy   | `#1e40af` | Primary accent: CTA utama, badge, link aktif | Warna h2 template modern, identitas app yang sudah dibangun (R-07 kolinieritas).                                           |
| Powder | `#b0e0e6` | Accent kedua: highlight, dekorasi background | User pilih (jawaban free text). Tidak bersaing dengan navy (R-25), tint dingin yang cocok dipasangkan dengan cool neutral. |
| White  | `#ffffff` | Surface card, input                          | Netral untuk konten dense.                                                                                                 |
| Error  | `#dc2626` | Alert error                                  | Konvensi darurat.                                                                                                          |

- Powder `#b0e0e6` untuk teks di atas paper kontrasnya 1.43:1, gagal AA. Selalu pasangkan dengan ink.
- Dark mode: background `#18181b` (zinc-900 abu standar), surface `#27272a` (zinc-800), teks `#f8fafc`, navy `#3b82f6` (lebih terang agar kontras), powder `#b0e0e6` tetap. Border `#f4f4f5` (zinc-100) agar terlihat, shadow `#09090b` (zinc-950) tetap terbaca di atas surface abu. Latar gelap bukan hitam murni (Material #121212 / zinc-900, Exa 2025). Kontras teks 16.93:1 (bg) / 14.24:1 (surface) PASS AA.
- CTA utama harus navy. Powder hanya untuk highlight dan dekorasi.

## 4. Tipografi

- Font stack: Tailwind default (system sans: `ui-sans-serif, system-ui, sans-serif`). Tanpa Google Font eksternal.
- Heading: weight `font-black` (900), sama seperti heading preview CV (kontinuitas, R-07).
- Body: weight `font-normal`, `text-slate-700` di light mode, max 12 kata per baris di landing.
- Angka besar di landing: `font-black tabular-nums`.

## 5. Copy

- Bahasa Indonesia santai profesional. Zero em dash (R-02).
- CTA spesifik, bukan generik (R-15): tombol utama "Buat CV pertama", CTA akhir "Coba sekarang, gratis".
- Tanpa fake stats, fake testimonial, atau klaim tidak verifikasi (R-17, R-18, R-36). Landing
  ResumeKan hanya memuat fitur yang benar-benar ada: 2 template ATS (modern dan classic),
  simpan draft, ringkasan AI, download PDF A4.
- Satu nilai utama per section, max 2 properti per card (R-11).

## 6. Motion (MOTION 1)

- Hover tombol: translate-y 2px + shadow mengecil (interaksi nyata, Slab reference).
- Scroll-reveal: fade + translate-y 12px, delay bertingkat max 60ms per card.
- Semua animasi hormati `prefers-reduced-motion` (R-12, Slab reference).
- Tanpa loop, tanpa parallax, tanpa smooth scroll.

## 7. Hero

- Mock browser window (border ink 3px, hard shadow, title bar ink dengan 3 dot) berisi preview CV
  asli dari komponen `CvPreview` yang dipakai di editor. Bukan ilustrasi, bukan screenshot palsu (C-5).
- Di atas preview: toggle Modern / Classic yang mengubah template preview live (bukti fitur template).
- Toggle preview full render, bukan gambar. Ini juga membuktikan template asli, bukan mock.

## 8. Rhythm Landing

| Bagian    | Background | Isi                                         |
| --------- | ---------- | ------------------------------------------- |
| Hero      | paper      | Judul + CTA + mock browser dengan CvPreview |
| Fitur     | ink        | 4 card fitur, teks putih, ikon garis        |
| Template  | paper      | Toggle modern/classic, preview asli         |
| CTA akhir | navy       | Tombol "Coba sekarang, gratis"              |

Variasi background mencegah modul identik beruntun (R-08, RHYTHM 2).

## 9. Component Library

Paket `neobrutalism-vue` (registry neobrutalism-vue.com, berbasis shadcn-vue dan Reka UI, Tailwind v4,
WAI-ARIA). Instal via `pnpm dlx shadcn-vue@latest add https://neobrutalism-vue.com/r/<component>.json`.
Komponen hasil install berupa source code di `web/src/components/ui`, langsung disesuaikan dengan
palet Ink & Navy. Komponen yang diperlukan untuk Fase 6: button, card, badge.

## 10. Dark Mode

- Strategy: class-based. Tailwind v4: `@custom-variant dark (&:where(.dark, .dark *));` di `main.css`, class `.dark` di `<html>`. FOUC guard inline script di `index.html` head (baca `localStorage` + `prefers-color-scheme` sebelum stylesheet load).
- Toggle 3-way di navbar: `auto → light → dark → auto` (`useDarkMode.ts` — `choice` ref, `isDark()` function, `cycle()`, `colorScheme` sync, `matchMedia` listener). `auto` = ikuti sistem (hapus key `resumekan-theme`), `light`/`dark` = simpan eksplisit. Default light (81.3% startup default light, audience B2B/trust lebih nyaman light).
- Token dark di `main.css`: `--background #18181b` (zinc-900), `--secondary-background #27272a` (zinc-800), `--foreground #f8fafc`, `--main #3b82f6`, `--border #f4f4f5`, `--shadow #09090b`, `color-scheme: dark`. Card fitur: `border-2 border-ink dark:border-border`, icon `bg-powder border-ink dark:border-border` + `text-ink` (fixed, kontras 12.46:1 di kedua mode).
- Cakupan: semua halaman dark-mode (landing, navbar, footer, login, register, dashboard, CV form). `CvPreview` dan template PDF tetap putih (dokumen kertas). `CvForm.vue` pakai scoped CSS dark untuk 40+ field (input/select/textarea/label/h2/p) agar tidak duplikasi `dark:` per-field.
- Kontras teks di kedua mode minimal AA (R-25). Tidak pakai warna yang sama untuk text dan background di dark mode (R-34).

## 11. Accessibility & Delivery Gate

- Semua komponen WAI-ARIA (dijamin shadcn-vue/Reka UI). Fokus keyboard terlihat (outline ink 2px).
- Kontras teks AA di light dan dark mode (R-25).
- Hover, focus, active, loading, disabled, error states lengkap di semua tombol (R-27).
- Jalankan Delivery Gate antislop sebelum commit: cek em dash, kontras, keyboard, states, run dan
  verify di browser.
