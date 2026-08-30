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
| `web/src/views/LoginView.vue`        | Login form + dark variants (main/card/input/link/button/error)                                                                                                                                                                                        |
| `web/src/views/RegisterView.vue`     | Register form + dark variants (main/card/4 input/link/button/error)                                                                                                                                                                                   |
| `web/src/views/DashboardView.vue`    | Dashboard + dark variants (bg/card/button/error/empty)                                                                                                                                                                                                |
| `web/src/views/CvFormView.vue`       | CV form + preview + dark variants (main/card/h1/back/draft/pdf/preview/toast)                                                                                                                                                                         |
| `web/src/components/cv/CvForm.vue`   | Scoped dark CSS untuk 40+ field (input/select/textarea/label/h2/p) + dark button Generate AI/Simpan CV                                                                                                                                                |

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
- `CvForm.vue` pakai scoped CSS dark untuk 40+ field agar tidak duplikasi `dark:` per-field.
- Verifikasi browser dark: login/register `main rgb(11,14,20)` card `rgb(30,41,59)` input `dark:text-foreground` button `rgb(59,130,246)`, navbar logo/link `rgb(248,250,252)` border `rgb(248,250,252)`.

### Hero — polish lanjutan

- Hapus badge pill `Badge "Generator CV ATS"` di atas headline (AI slop — pill/eyebrow badge, Exa pols.dev/slop.md, antislop-ui). Headline `mt-4` dihapus.
- Hero spacing `py-16 lg:py-24` → `py-10 lg:py-14` + teks `lg:-translate-y-12` (naik, CTA di atas fold, antislop-ui Uniform Spacing, transform tanpa layout shift).
- Preview: `h-[360px] p-3 scale-[0.72]/[0.75]` → `h-[520px] @[520px]:h-[540px] p-0 scale-[0.72]/[0.85] origin-top` tanpa scroll, margin kanan-kiri maksimal, simetris dengan kolom kiri. Border `3px`→`1.5px`/`2px` shadow `8px`→`4px`/`6px` zinc-950.

### FAQ — ganti section Template (2026-08-30)

- Section 3 `Template (Modern/Classic 2 col)` → `FAQ` (6 Q accordion, jawaban umum tanpa istilah teknis, CTA `Buat CV pertama →` di Q3). Landing jadi `Hero → Fitur → FAQ → CTA`.
- Q5 `Bagaimana AI summary bekerja? Perlu isi apa dulu?` — jawaban umum: isi pengalaman/keahlian dulu, AI buatkan ringkasan, bisa edit, tidak wajib.
- Implementasi: `ref openFaq`, `ChevronDown` rotate-180, `border-2 border-ink shadow-shadow dark:border-border`, `aria-expanded`, tanpa dependency baru. Exa: 5-8 Q, <80 kata, lead Ya/Tidak, accordion table stakes, CTA di jawaban (edge 30%→78% best-in-class).

### Dark mode — abu Opsi A (2026-08-30, uncommitted)

- Token dark `main.css`: `#18181b` (zinc-900) → `#27272a` (zinc-800) page, `#27272a` (zinc-800) → `#3f3f46` (zinc-700) surface, shadow `#09090b` (zinc-950) → `#18181b` (zinc-900). Light tetap. Kontras `14.2:1`/`10.0:1` AAA, border `13.6:1`, elevation `1.4:1`. Exa: never pure black, elevation via lightness.

## Hasil Verifikasi

- vue-tsc 0 error
- pnpm build sukses
- Semua section landing terlihat: hero (CvPreview modern), fitur 4 card, FAQ 6 Q accordion, CTA navy
- Dark mode 2-way toggle berfungsi (class-based, localStorage, colorScheme, tanpa matchMedia)
- Semua halaman dark-mode terverifikasi di browser (login, register, navbar, footer, CV form)
- SSR dan kustom utility Tailwind tercompile

## Batasan (dari PRD)

- Landing **statis** — tanpa blog/CMS.
- Non-fitur v1 tetap berlaku: tanpa paywall, foto profil, share link, OAuth, ATS checker ([PRD §5](../PRD.md#5-non-fitur-v1-dilarang-dibangun-sekarang)).

## Definisi Selesai

- Semua kriteria selesai MVP di [PRD §7](../PRD.md#7-kriteria-selesai-mvp) tercentang.

← [Fase 5](phase-5-pdf.md) · Setelah ini: Deploy (lihat [Roadmap](../ROADMAP.md#deploy-nanti-bukan-mvp))
