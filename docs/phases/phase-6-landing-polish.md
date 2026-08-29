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

| File                                 | Keterangan                                                                                                  |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------- |
| `docs/DESIGN.md`                     | Design guide (dials, palet, copy, motion, rhythm, dark mode, accessibility)                                 |
| `web/components.json`                | Konfigurasi shadcn-vue                                                                                      |
| `web/src/lib/utils.ts`               | Utility `cn()` untuk neobrutalism components                                                                |
| `web/src/composables/useDarkMode.ts` | Dark mode 3-way (auto/light/dark) + localStorage + prefers-color-scheme + colorScheme sync + matchMedia listener |
| `web/src/components/AppNavbar.vue`   | Navbar sticky (border-2 ink) + neobrutalism button + 3-way toggle (Monitor/Moon/Sun) + dark variants semua link/button |
| `web/src/components/AppFooter.vue`   | Footer minimal + dark variants (border + text)                                                              |
| `web/src/views/HomeView.vue`         | Landing: hero (mock browser + CvPreview toggle), fitur (4 card, bg ink), template (2 col preview), CTA navy + dark variants semua section |
| `web/src/views/LoginView.vue`        | Login form + dark variants (main/card/input/link/button/error)                                             |
| `web/src/views/RegisterView.vue`     | Register form + dark variants (main/card/4 input/link/button/error)                                        |
| `web/src/views/DashboardView.vue`    | Dashboard + dark variants (bg/card/button/error/empty)                                                     |
| `web/src/views/CvFormView.vue`       | CV form + preview + dark variants (main/card/h1/back/draft/pdf/preview/toast)                              |
| `web/src/components/cv/CvForm.vue`   | Scoped dark CSS untuk 40+ field (input/select/textarea/label/h2/p) + dark button Generate AI/Simpan CV     |

### Files modified

- `web/src/assets/main.css`: @custom-variant dark, CSS variable tokens (--main, --border, --shadow, --box-shadow-x), @theme inline mapping, powder/ink/navy/paper utility, dark tokens (--background #0b0e14, --secondary-background #1e293b, --foreground #f8fafc, --main #3b82f6, --border #f8fafc, --shadow #04060a), color-scheme light/dark
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

### Dark mode — perbaikan all-pages (commit e4201b4)

- FOUC guard inline script di `index.html` head (baca `resumekan-theme` + `prefers-color-scheme` sebelum stylesheet).
- `useDarkMode.ts` 3-way: `auto → light → dark → auto` (`choice` ref, `isDark()` function, `cycle()`, `colorScheme` sync, `matchMedia` listener). `auto` hapus key localStorage.
- Token dark: `--background #0b0e14`, `--secondary-background #1e293b`, `--foreground #f8fafc`, `--main #3b82f6`, `--border #f8fafc`, `--shadow #04060a`.
- Semua halaman dark-mode: landing, navbar, footer, login, register, dashboard, CV form. `CvPreview`/PDF tetap putih (dokumen kertas).
- `CvForm.vue` pakai scoped CSS dark untuk 40+ field agar tidak duplikasi `dark:` per-field.
- Verifikasi browser dark: login/register `main rgb(11,14,20)` card `rgb(30,41,59)` input `dark:text-foreground` button `rgb(59,130,246)`, navbar logo/link `rgb(248,250,252)` border `rgb(248,250,252)`.

## Hasil Verifikasi

- vue-tsc 0 error
- pnpm build sukses
- Semua section landing terlihat: hero (CvPreview modern), fitur 4 card, template 2 col, CTA navy
- Dark mode 3-way toggle berfungsi (class-based, localStorage, colorScheme, matchMedia)
- Semua halaman dark-mode terverifikasi di browser (login, register, navbar, footer, CV form)
- SSR dan kustom utility Tailwind tercompile

## Batasan (dari PRD)

- Landing **statis** — tanpa blog/CMS.
- Non-fitur v1 tetap berlaku: tanpa paywall, foto profil, share link, OAuth, ATS checker ([PRD §5](../PRD.md#5-non-fitur-v1-dilarang-dibangun-sekarang)).

## Definisi Selesai

- Semua kriteria selesai MVP di [PRD §7](../PRD.md#7-kriteria-selesai-mvp) tercentang.

← [Fase 5](phase-5-pdf.md) · Setelah ini: Deploy (lihat [Roadmap](../ROADMAP.md#deploy-nanti-bukan-mvp))
