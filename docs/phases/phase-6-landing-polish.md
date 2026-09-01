# Fase 6 — Landing & Polish

> Status: **Selesai** · Eksekusi: 2026-08-29

## Rencana Kerja

- [x] Landing page (hero, fitur, CTA) + animasi ringan (@vueuse/motion)
- [x] Dark mode, responsive check, empty states

## Implementation

### Design (docs/DESIGN.md)

- **Style**: Neo-brutalism profesional (border 2px ink, hard shadow 4px, warna flat)
- **Palet Ink & Navy**: ink #0f172a, paper #f8fafc, navy #1e40af, powder #b0e0e6
- **Dials**: ENERGY 2, RHYTHM 2, MOTION 1
- **Copy**: Bahasa Indonesia, tanpa em dash, CTA spesifik (R-15)
- Referensi: madegooddesigns.com, alexmayhew.dev, neobrutalism-vue.com

### Dependencies

- `shadcn-vue` (CLI) + `class-variance-authority`, `clsx`, `tailwind-merge`, `lucide-vue-next`, `reka-ui`, `tw-animate-css`
- `@vueuse/motion` 3.0.3 — directive v-motion scroll-reveal
- `neobrutalism-vue` registry: button, card, badge (install lewat `pnpm dlx shadcn-vue@latest add`)

### Files created

| File                                 | Keterangan                                                                                                                                                                                                                                            |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `docs/DESIGN.md`                     | Design guide (dials, palet, copy, motion, rhythm, dark mode, accessibility)                                                                                                                                                                           |
| `web/components.json`                | Konfigurasi shadcn-vue                                                                                                                                                                                                                                |
| `web/src/lib/utils.ts`               | Utility `cn()` untuk neobrutalism components                                                                                                                                                                                                          |
| `web/src/composables/useDarkMode.ts` | Dark mode 3-way (auto/light/dark) + localStorage + prefers-color-scheme + colorScheme sync + matchMedia listener                                                                                                                                      |
| `web/src/components/AppNavbar.vue`   | Navbar sticky (border-2 ink) + neobrutalism button + 3-way toggle (Monitor/Moon/Sun) + dark variants semua link/button                                                                                                                                |
| `web/src/components/AppFooter.vue`   | Footer minimal + dark variants (border + text)                                                                                                                                                                                                        |
| `web/src/views/HomeView.vue`         | Landing: hero (mock browser + CvPreview toggle, tanpa badge pill, py-10 lg:py-14, preview h-[520px] @[520px]:h-[540px] scale 0.72/0.85), fitur (4 card, bg ink), FAQ (6 Q accordion, jawaban umum, CTA di Q3), CTA navy + dark variants semua section |
| `web/src/views/LoginView.vue`        | Login split panel (2026-08-30): aside ink pitch panel (desktop) + form card neobrutalism, label eksplisit, autocomplete, show/hide password, error aria-live, toggle dark mode di card, semua warna token (navy/ink/paper/powder/error)               |
| `web/src/views/RegisterView.vue`     | Register split panel (2026-08-30): mirror login — aside ink pitch panel + form card neobrutalism, label eksplisit, autocomplete name/email/new-password, show/hide password, error aria-live, toggle dark di card, bg-dots panel kanan                |
| `web/src/views/DashboardView.vue`    | Dashboard + dark variants (bg/card/button/error/empty)                                                                                                                                                                                                |
| `web/src/views/CvFormView.vue`       | CV form + preview + dark variants (main/card/h1/back/draft/pdf/preview/toast)                                                                                                                                                                         |
| `web/src/components/cv/CvForm.vue`   | Shell form (refactor 2026-08-31: isi pindah ke `steps/*.vue` + `form/*.vue`; dark CSS field pindah ke `<style>` non-scoped bernamespace `.cv-form` di `CvForm.vue`) + stepper tabs 9 langkah (2026-08-30)                                             |

### Files modified

- `web/src/assets/main.css`: @custom-variant dark, CSS variable tokens (--main, --border, --shadow, --box-shadow-x), @theme inline mapping, powder/ink/navy/paper utility, dark tokens (--background #27272a zinc-800, --secondary-background #3f3f46 zinc-700, --foreground #f8fafc, --main #3b82f6, --border #f4f4f5, --shadow #18181b zinc-900), color-scheme light/dark — Opsi A abu medium (update 2026-08-30, sebelumnya #18181b/#27272a/#09090b)
- `web/src/main.ts`: register MotionPlugin
- `web/src/App.vue`: AppNavbar + AppFooter + initTheme() + bg-paper/text-ink wrapper
- `web/src/router/index.ts`: "/" → HomeView (redirect /dashboard dihapus)
- `web/tsconfig.json`: baseUrl + paths @/\*
- `web/index.html`: title "ResumeKan" + FOUC guard inline script (localStorage + prefers-color-scheme sebelum stylesheet load)

### Architecture notes

- Router "/" sekarang render HomeView, bukan redirect ke /dashboard
- DashboardView tetap di `/dashboard` (requiresAuth)
- Login/Register tetap standalone (bukan partial di landing)
- CvPreview di hero menggunakan data contoh `sample` (bukan fake stats/testimonial, per R-17/R-18)

### Dark mode — perbaikan all-pages (commit e4201b4, revisi 2026-08-30 jadi 2-way)

- FOUC guard inline script di `index.html` head (hanya `resumekan-theme === "dark"`, tanpa `prefers-color-scheme`; default light untuk semua).
- `useDarkMode.ts` 2-way: `light ↔ dark` (`choice` ref `light|dark`, `isDark()` function, `cycle()`, `colorScheme` sync). Tanpa `auto`/`systemDark`/`matchMedia`.
- Token dark: `--background #27272a` (zinc-800), `--secondary-background #3f3f46` (zinc-700), `--foreground #f8fafc`, `--main #3b82f6`, `--border #f4f4f5`, `--shadow #18181b` (zinc-900) — Opsi A abu medium 2026-08-30 (sebelumnya #18181b/#27272a/#09090b, kontras 14.2:1/10.0:1 AAA, elevation 1.4:1).
- Semua halaman dark-mode: landing, navbar, footer, login, register, dashboard, CV form. `CvPreview`/PDF tetap putih (dokumen kertas).
- `CvForm.vue` pakai CSS dark non-scoped bernamespace `.cv-form` untuk 40+ field agar tidak duplikasi `dark:` per-field (refactor 2026-08-31: scoped CSS tidak menembus child component `steps/`/`form/`).
- Verifikasi browser dark: login/register `main rgb(11,14,20)` card `rgb(30,41,59)` input `dark:text-foreground` button `rgb(59,130,246)`, navbar logo/link `rgb(248,250,252)` border `rgb(248,250,252)`.

### Hero — polish lanjutan

- Hapus badge pill `Badge "Generator CV ATS"` di atas headline (AI slop — pill/eyebrow badge, Exa pols.dev/slop.md, antislop-ui). Headline `mt-4` dihapus.
- Hero spacing `py-16 lg:py-24` → `py-10 lg:py-14` + teks `lg:-translate-y-12` (naik, CTA di atas fold, antislop-ui Uniform Spacing, transform tanpa layout shift).
- Preview: `h-[360px] p-3 scale-[0.72]/[0.75]` → `h-[520px] @[520px]:h-[540px] p-0 scale-[0.72]/[0.85] origin-top` tanpa scroll, margin kanan-kiri maksimal, simetris dengan kolom kiri. Border `3px`→`1.5px`/`2px` shadow `8px`→`4px`/`6px` zinc-950.

### FAQ — ganti section Template (2026-08-30)

- Section 3 `Template (Modern/Classic 2 col)` → `FAQ` (6 Q accordion, jawaban umum tanpa istilah teknis, CTA `Buat CV pertama →` di Q3). Landing jadi `Hero → Fitur → FAQ → CTA`.
- Q5 `Bagaimana AI summary bekerja? Perlu isi apa dulu?` — jawaban umum: isi pengalaman/keahlian dulu, AI buatkan ringkasan, bisa edit, tidak wajib.
- Implementasi: `ref openFaq`, `ChevronDown` rotate-180, `border-2 border-ink shadow-shadow dark:border-border`, `aria-expanded`, tanpa dependency baru. Exa: 5-8 Q, <80 kata, lead Ya/Tidak, accordion table stakes, CTA di jawaban (edge 30%→78% best-in-class).

### Auth layout minimal (2026-08-30)

- Login/register full-screen tanpa navbar/footer (redundansi: "ResumeKan" 2x, "Masuk" 3x, footer tidak relevan di auth). `App.vue` render `AppNavbar`/`AppFooter` hanya jika `!route.meta.authLayout`; router meta `authLayout: true` di `/login` + `/register`.
- Toggle dark mode pindah ke card login (navbar hilang di auth page). Landing/dashboard tetap navbar+footer.
- Verifikasi browser: login tanpa navbar/footer, toggle dark di card berfungsi, landing tetap normal.

### Dark mode — abu Opsi A (2026-08-30, uncommitted)

- Token dark `main.css`: `#18181b` (zinc-900) → `#27272a` (zinc-800) page, `#27272a` (zinc-800) → `#3f3f46` (zinc-700) surface, shadow `#09090b` (zinc-950) → `#18181b` (zinc-900). Light tetap. Kontras `14.2:1`/`10.0:1` AAA, border `13.6:1`, elevation `1.4:1`. Exa: never pure black, elevation via lightness.

### CvForm — audit dark mode elemen non-field (2026-08-30, uncommitted)

- Audit computed style di browser: elemen non-field masih gaya light di atas card zinc-700 — `+ Tambah` (slate-700, kontras 1.01:1), label `#N` (slate-500, 2.19:1), `Hapus` (red-600, 2.16:1), card section (border-slate-200 tanpa token). Field/label/h2/counter/empty state sudah benar via scoped CSS.
- Fix: `dark:` variant dengan token, pola sama dengan tombol header `CvFormView.vue` — `+ Tambah` → `dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground` (6.42:1), `#N` → `dark:text-foreground/60` (5.29:1), `Hapus` → `dark:text-red-300` (5.50:1), card → `dark:border-border`.
- Hover states disinkronkan kedua mode (R-27): light `hover:bg-slate-200 hover:text-slate-700` (delta jelas, teks 8.40:1), dark `dark:hover:bg-white/15 dark:hover:text-foreground` (overlay terang di atas zinc-700, 6.35-7.44:1). Hover adalah treatment suplemen (pointer = state indicator, SC 1.4.11 tidak wajib 3:1 untuk bg), tapi teks tetap ≥4.5:1.
- Verifikasi browser: dark (semua elemen terbaca, kontras AA) + light (hover slate-200 terlihat, teks slate-700, tidak ada regresi). vue-tsc 0 error.

### CvForm — stepper tabs 9 langkah (2026-08-30, uncommitted)

- Form panjang 9 section satu scroll (scrollHeight 4107px) terlalu overwhelm. Inspirasi: FlowCV (multi-step wizard, `currentStep` state, Next/Back, progress tracking), Rezi UX audit (Exa: "maze-like navigation without clear indicators of progress" — fix: visual progress indicators, step-by-step guided navigation, Next button). Implementasi Opsi A: stepper tabs horizontal di atas form.
- 9 langkah: Info, Pribadi, Ringkasan, Pengalaman, Pendidikan, Organisasi, Keahlian, Proyek, Lainnya. Chip bernomor 3 state: active (`bg-slate-900`/`dark:bg-main`), completed (`✓` emerald), upcoming (`text-slate-500`, 4.76:1 PASS; sebelumnya slate-400 2.56:1 gagal). Klikable — user bisa lompat ke step mana saja. `v-show` per section (bukan `v-if`) agar state field tidak hilang saat pindah step. Prev/Next button di bawah form + "Langkah N/9" indicator. Di step terakhir Next berubah jadi Simpan CV.
- Implementasi: `activeStep` ref + `steps` array, `v-show="activeStep === N"` per section, nav HTML + scoped CSS `.dark nav`. ~60 baris baru, tidak ada file/dependency baru, tidak split komponen. Preview kanan tetap real-time.
- Verifikasi browser: light (step 1→2→3, jump ke 4, jump ke 9 Simpan CV muncul) + dark (stepper nav terbaca, active chip navy, completed ✓ hijau, upcoming muted). vue-tsc 0 error.

### Tekstur landing — dot grid + powder tint (2026-08-30)

- Landing flat (2 section paper identik beruntun) → Opsi A+B: `.bg-dots` utility di `main.css` (radial-gradient dot 1px/20px, light ink 46%, dark foreground 24% — revisi dari 8%/7% karena nyaris tak terlihat; Exa: alpha 0.05-0.1 terlalu halus untuk dot sparse) di hero + FAQ; FAQ tambah `bg-powder/40` (light) — powder akhirnya terpakai di landing. Section ink & navy tetap flat (hierarki).
- Alasan identitas (R-07): motif kertas CV. Exa webuiprompt neo-brutalism: halftone dots critical for depth. Dose cap 2 section (R-12). Tanpa dependency, tanpa gambar.
- Verifikasi browser: hero/FAQ dots ter-render light & dark, FAQ bg powder/40 light + zinc-800 dark. vue-tsc 0 error.

### Login — polish lanjutan (2026-08-30, uncommitted)

- Badge `/ masuk` di samping h1 dihapus (non-fungsional, redundan dengan h1 — antislop R-09). h1 `Masuk` langsung di bawah toggle dark.
- Panel kanan login dapat `bg-dots` (tekstur sama dengan hero/FAQ, dose cap jadi 3 permukaan paper). Verifikasi browser: dots terlihat light (ink 46% di atas paper) dan dark (foreground 24% di atas zinc-800).

### Register — brutalisasi konsisten login (2026-08-30, uncommitted)

- Register gaya lama (flat, `bg-slate-100`, `rounded-xl`, `blue-600`, placeholder-only labels) → split panel mirror login: aside ink pitch (headline "Satu akun, semua CV kamu." + 3 bullet fitur akun: draft, AI summary, PDF) + form card neobrutalism dengan bg-dots.
- UX sama dengan login: label eksplisit, autocomplete `name`/`email`/`new-password`, show/hide password (mengubah kedua field password), error `aria-live`, toggle dark di card, tombol navy shadow brutal.
- Verifikasi browser: light (card putih + dots paper) dan dark (card zinc-700 + dots zinc-800) ter-render benar. vue-tsc 0 error.

## Hasil Verifikasi

- vue-tsc 0 error
- pnpm build sukses
- Semua section landing terlihat: hero (CvPreview modern), fitur 4 card, FAQ 6 Q accordion, CTA navy
- Dark mode 2-way toggle berfungsi (class-based, localStorage, colorScheme, tanpa matchMedia)
- Semua halaman dark-mode terverifikasi di browser (login, register, navbar, footer, CV form)
- SSR dan kustom utility Tailwind tercompile

## Batasan (dari PRD)

- Landing **statis** — tanpa blog/CMS.
- Non-fitur v1 tetap berlaku: tanpa paywall, share link, OAuth, ATS checker ([PRD §5](../PRD.md#5-non-fitur-v1-dilarang-dibangun-sekarang)).

## Definisi Selesai

- Semua kriteria selesai MVP di [PRD §7](../PRD.md#7-kriteria-selesai-mvp) tercentang.

← [Fase 5](phase-5-pdf.md) · Setelah ini: Deploy (lihat [Roadmap](../ROADMAP.md#deploy-nanti-bukan-mvp))
