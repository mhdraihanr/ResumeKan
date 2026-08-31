# Rencana Refactor Struktur — ResumeKan

> **Tujuan:** rapikan struktur kode yang menumpuk tanpa mengubah perilaku & tampilan.
> **Prinsip:** deletion over addition, boring over clever, shortest diff. Tidak ada perubahan visual, tidak ada perubahan API, tidak ada dependency baru.
> **Status:** ✅ selesai (2026-08-31) — semua fase dieksekusi & diverifikasi via browser (light+dark, classic+modern).

## 1. Latar Belakang

Audit 2026-08-31 menemukan 2 file menumpuk:

| File                                  | Baris     | Masalah                                                                  |
| ------------------------------------- | --------- | ------------------------------------------------------------------------ |
| `web/src/components/cv/CvForm.vue`    | 928       | 9 section inline, duplikasi kelas 36×/26×/39×, hack `syncing` flag       |
| `web/src/components/cv/CvPreview.vue` | 577       | 2 template duplikat penuh, dead code `contactLine`, 6× ul bullet identik |
| `api/`                                | ≤121/file | ✅ sehat                                                                 |

Duplikasi terukur: input class `rounded-lg border…` 36×, `placeholder:text-slate-400…` 26×, label span 39×, tombol `+ Tambah` 4×, `Hapus` 4×, heading h2 13×, `flex items-baseline…` 6×.

Riset:

- Vue SFC docs — bagi jadi loosely-coupled components yang di-compose, bukan mega-file.
- Tailwind Managing Duplication — urutan: loop → multi-cursor → **component** → custom CSS. Jangan `@apply` (Tailwind #19195).
- DEV "When Logic Belongs in a Composable" — split karena tanggung jawab berbeda, bukan karena panjang.

## 2. Tujuan & Batasan

**Tujuan:**

- Turunkan file >300 baris jadi <200 baris per file.
- Hilangkan duplikasi kelas Tailwind (36× → 1 sumber).
- Hilangkan dead code & hack sync.
- Kurangi triple duplication preview (Vue modern + Vue classic + Blade PDF).

**Bukan tujuan (non-goals):**

- Tidak ubah tampilan (pixel-identical).
- Tidak ubah perilaku (validasi, submit, AI, PDF).
- Tidak tambah dependency.
- Tidak ubah backend (sudah sehat).
- Tidak ubah `HomeView.vue` 323 baris (masih wajar).

## 3. Struktur Target

```
web/src/components/cv/
├── CvForm.vue              # ~150 baris: stepper nav + step nav + state
├── form/                   # 1 sumber kelas input (hapus 36 duplikasi)
│   ├── FormInput.vue
│   ├── FormTextarea.vue
│   ├── FormSelect.vue
│   └── FormLabel.vue
├── steps/                  # 1 tanggung jawab per step (80-120 baris)
│   ├── MetaStep.vue        # options dari CV_TEMPLATES (token)
│   ├── PersonalStep.vue
│   ├── SummaryStep.vue
│   ├── ExperienceStep.vue
│   ├── EducationStep.vue
│   ├── OrganizationStep.vue
│   ├── SkillsStep.vue
│   ├── ProjectsStep.vue
│   └── OtherStep.vue
├── CvPreview.vue           # 1 sumber section, token-driven (header + otherMode)
├── sections/               # dipakai semua template
│   ├── PreviewSection.vue  # h2 + border via :modern
│   ├── EntryRow.vue        # title kiri + tanggal kanan
│   └── BulletList.vue
web/src/lib/
└── cv-templates.ts         # token 1 sumber: font, headerAlign, h1Class, linkClass, otherMode
```

## 4. Fase Eksekusi

### Fase 0 — Quick wins (tanpa ubah struktur file)

- Hapus dead code `contactLine` di `CvPreview.vue`.
- Ganti hack `syncing` flag + `setTimeout(0)` di `CvForm.vue` → `defineModel` (Vue 3.5).
- Hapus `normalizeProjects()` + 4× cast `as unknown` di `CvForm.vue` (backend sudah normalisasi).
- Verifikasi: `get_errors` 0, browser light+dark identik.

### Fase 1 — Extract form base components

- Buat `form/FormInput.vue`, `FormTextarea.vue`, `FormSelect.vue`, `FormLabel.vue`.
- Pindahkan kelas duplikat + dark token + `auto-expand` logic ke component.
- Ganti 36×/26×/39× duplikasi di `CvForm.vue` dengan component.
- Verifikasi: `get_errors` 0, screenshot before/after identik.

### Fase 2 — Split 9 steps

- Buat `steps/*.vue` (9 file), masing-masing terima `modelValue` via `defineModel`/`props`.
- `CvForm.vue` sisa stepper nav + `activeStep` + step navigation.
- `v-show="activeStep === N"` tetap di parent (state persist).
- Verifikasi: klik stepper 1→9, Prev/Next, tambah/hapus entry, counter.

### Fase 3 — Extract preview sections + token 1 sumber

- Buat `sections/PreviewSection.vue`, `EntryRow.vue`, `BulletList.vue`.
- Buat `lib/cv-templates.ts` — token 1 sumber (`font`, `headerAlign`, `nameUppercase`, `h1Class`, `linkClass`, `otherMode`).
- `CvPreview.vue` jadi 1 sumber section (hapus duplikasi `v-if isModern` / `v-else` ~130 baris) — header & `Lainnya` vs `Sertifikasi`/`Bahasa` via `tpl.otherMode`, font/border via token.
- `MetaStep.vue` & `HomeView.vue` baca `CV_TEMPLATES` untuk options (tambah template = tambah 1 entry token).
- Verifikasi: preview modern & classic identik (browser), PDF Blade belum diubah.

### Fase 4 — Sinkron PDF (Opsi C token-based)

- **Dilakukan (Opsi C):** token `cv-templates.ts` jadi 1 sumber keputusan untuk nambah template (tambah 1 entry, bukan copy 130 baris). `CvPreview.vue` single-source section.
- **Opsi B tetap:** perubahan layout CV wajib sinkron `CvPreview.vue` + `cv.blade.php` (ADR-4). Drift kontak 1-baris vs 2-baris didokumentasikan.
- **Ditunda (Opsi A):** Browsershot load URL SPA print — butuh route print + auth headless, terlalu besar untuk refactor tanpa perubahan perilaku. Upgrade path tercatat di ADR-4.
- Verifikasi akhir: `get_errors` 0, `pnpm build` sukses, browser light+dark, preview modern & classic identik.

## 5. Verifikasi Tiap Fase

1. `get_errors` 0.
2. `pnpm build` sukses (jika ada).
3. Browser tools: `readPage` + `screenshotPage` + `runPlaywrightCode` (hover, stepper klik, tambah/hapus).
4. Bandingkan screenshot before/after — harus identik.
5. Cek dark mode + light mode.

## 6. Risiko & Mitigasi

| Risiko                        | Mitigasi                                                             |
| ----------------------------- | -------------------------------------------------------------------- |
| Visual drift (kelas hilang)   | Component bawa kelas identik, verifikasi screenshot tiap fase        |
| State hilang saat pindah step | `v-show` tetap di parent, bukan `v-if`                               |
| PDF drift dari preview        | Fase 4 single-source atau dokumentasi sinkron                        |
| Over-abstraksi                | Hanya split yang ada tanggung jawab jelas, tidak buat factory/config |

## 7. Urutan Commit (disarankan)

1. `refactor(cv-form): hapus dead code, defineModel, hapus normalisasi FE`
2. `refactor(cv-form): extract FormInput/Textarea/Select/Label`
3. `refactor(cv-form): split 9 steps jadi komponen terpisah`
4. `refactor(preview): extract PreviewSection/EntryRow/BulletList + token cv-templates`
5. `docs: sinkron REFACTOR_PLAN + ADR-4 untuk Opsi C token-based`

## 8. Referensi

- Vue SFC docs — https://vuejs.org/guide/scaling-up/sfc.html
- Tailwind Managing Duplication — https://tailwindcss.com/docs/styling-with-utility-classes
- Tailwind @apply is evil #19195 — https://github.com/tailwindlabs/tailwindcss/discussions/19195
- DEV "When Logic Belongs in a Composable" — https://dev.to/lazydoomslayer/how-i-build-vue-3-applications-part-3-when-logic-belongs-in-a-composable-2bpe
- vuejsdevelopers Extending Templates — https://vuejsdevelopers.com/2020/02/24/extending-vuejs-components-templates/
