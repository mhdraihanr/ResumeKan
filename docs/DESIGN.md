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

| Token  | Hex       | Fungsi                                       | Alasan                                                                                                                     |
| ------ | --------- | -------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Ink    | `#0f172a` | Border, teks utama, section gelap            | Warna teks default preview dan template classic, sudah ada di seluruh app (kontinuitas brand).                             |
| Paper  | `#f8fafc` | Background utama                             | Neuter, tidak kompetisi dengan accent.                                                                                     |
| Navy   | `#1e40af` | Primary accent: CTA utama, badge, link aktif | Warna h2 template modern, identitas app yang sudah dibangun (R-07 kolinieritas).                                           |
| Powder | `#b0e0e6` | Accent kedua: highlight, dekorasi background | User pilih (jawaban free text). Tidak bersaing dengan navy (R-25), tint dingin yang cocok dipasangkan dengan cool neutral. |
| White  | `#ffffff` | Surface card, input                          | Netral untuk konten dense.                                                                                                 |
| Error  | `#dc2626` | Alert error                                  | Konvensi darurat.                                                                                                          |

- Powder `#b0e0e6` untuk teks di atas paper kontrasnya 1.43:1, gagal AA. Selalu pasangkan dengan ink.
- Dark mode: background `#27272a` (zinc-800 abu medium Opsi A), surface `#3f3f46` (zinc-700 terangkat), teks `#f8fafc`, navy `#3b82f6` (lebih terang agar kontras), powder `#b0e0e6` tetap. Border `#f4f4f5` (zinc-100) agar terlihat di abu, shadow `#18181b` (zinc-900) tetap terbaca di atas surface abu. Latar gelap bukan hitam murni (alexmayhew.dev).
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

- Mock browser window (border ink 2px, hard shadow `6px` terang di dark `#f4f4f5`, title bar ink dengan 3 dot) berisi preview CV asli dari komponen `CvPreview` yang dipakai di editor. Bukan ilustrasi, bukan screenshot palsu (C-5).
- Struktur entry Experience di preview (kedua template): baris 1 `Posisi · Perusahaan` + tanggal kanan, baris 2 metadata `Employment Type · Lokasi`, lalu bullets. Metadata tidak campur title line (konsisten pola Education).
- Struktur entry Organisasi di preview (kedua template): baris 1 `Nama Organisasi` (bold) + periode kanan, baris 2 `Peran` (slate-500), lalu bullets. Organisasi sebagai entitas utama di baris 1, peran sebagai detail di baris 2, kontras dengan pola Experience (peran di atas) karena organisasi lebih penting untuk identitas.
- Struktur entry Proyek di preview (kedua template): baris 1 `Judul Proyek` (bold), baris 2 `Objective` (slate-700), baris 3 `Peran: ...` (slate-500 labeled), baris 4 `Tech Stack: ...` (slate-500 labeled). Hierarki: title sebagai entitas, objective sebagai deskripsi, role + tech stack sebagai metadata berlabel (Exa ATScore: name → description → tools; TMJ Studio: stack di akhir).
- Di atas preview: toggle Modern / Classic yang mengubah template preview live (bukti fitur template).
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
- Cakupan: semua halaman dark-mode (landing, navbar, footer, login, register, dashboard, CV form). `CvPreview` dan template PDF tetap putih (dokumen kertas). `CvForm.vue` pakai scoped CSS dark untuk 40+ field (input/select/textarea/label/h2/p) agar tidak duplikasi `dark:` per-field. Elemen non-field (tombol `+ Tambah`, label `#N`, `Hapus`, card section, stepper nav) pakai `dark:` variant dengan token (`foreground/70`, `foreground/60`, `red-300`, `border`) — kontras di atas surface zinc-700 minimal AA (audit 2026-08-30: sebelumnya slate-700/slate-500/red-600 kontras 1.01-2.19:1, gagal). Hover states disinkronkan kedua mode: light `hover:bg-slate-200 hover:text-slate-700`, dark `dark:hover:bg-white/15 dark:hover:text-foreground`.
- CV form stepper: 9 langkah (Info, Pribadi, Ringkasan, Pengalaman, Pendidikan, Organisasi, Keahlian, Proyek, Lainnya). Chip bernomur 3 state (active navy, completed ✓ emerald, upcoming muted). Klikable, `v-show` per section (state field persist). Prev/Next + "Langkah N/9" indicator. Simpan CV di step terakhir. Inspirasi: FlowCV wizard, Rezi UX audit (Exa: progress indicator + guided navigation).
- Kontras teks di kedua mode minimal AA (R-25). Tidak pakai warna yang sama untuk text dan background di dark mode (R-34).

## 11. Accessibility & Delivery Gate

- Semua komponen WAI-ARIA (dijamin shadcn-vue/Reka UI). Fokus keyboard terlihat (outline ink 2px).
- Kontras teks AA di light dan dark mode (R-25).
- Hover, focus, active, loading, disabled, error states lengkap di semua tombol (R-27).
- Jalankan Delivery Gate antislop sebelum commit: cek em dash, kontras, keyboard, states, run dan
  verify di browser.
