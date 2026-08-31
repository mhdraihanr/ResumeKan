<!-- antislop:start -->

## Antislop Rules

Before starting or executing **anything**, check whether Antislop applies.

If it applies:

1. Ask the user whether Antislop should be applied **during the work** or **after it is done**.
2. Before implementation, load the required skills from `.github/skills/`:
   - `antislop` → Always required
   - `antislop-ui` → UI / visual
   - `antislop-copywriting` → Copy / text
   - `antislop-human` → People
   - `antislop-layoutmobile` → Mobile / responsive
   - `antislop-code` → Code comments
3. Load **all applicable skills** before doing any work.
4. Do not execute commands, modify files, or start implementation before the required skills are loaded and the user has answered.

<!-- antislop:end -->

### Testing Rules

- When testing is required, **prioritize testing through GitHub Copilot Web in VS Code**.
- Only use alternative testing methods when Copilot Web in VS Code is unavailable or unsuitable.
- Do not use external testing tools or methods unless explicitly instructed by the user.

### UI Verification Rules (Browser Tools)

Setiap perubahan UI **wajib diverifikasi lewat integrated browser VS Code** (browser tools), bukan hanya dari kode. Ikuti loop tertutup ini:

1. **Edit kode** → pastikan dev server berjalan (`pnpm dev` di `web/`, default `http://localhost:5173`).
2. **Buka/navigasi** halaman target di integrated browser (`openBrowserPage` / `navigatePage`). Jika user sudah share tab, pakai tab itu — jangan buka baru.
3. **Periksa hasil** dengan minimal dua dari:
   - `readPage` — snapshot aksesibilitas: struktur, heading, tombol, teks yang benar.
   - `screenshotPage` — verifikasi visual: layout, spacing, warna, dark mode.
   - `runPlaywrightCode` — untuk cek yang butuh skrip: posisi sticky saat scroll, ukuran elemen, state interaktif.
4. **Interaksi bila relevan** — klik stepper, isi form, submit, cek toast/error (`clickElement`, `typeInPage`).
5. **Laporkan hasil** — sebutkan apa yang diuji dan hasilnya (mis. "bar sticky terkonfirmasi di top: 0 saat scroll, 0 error").
6. **Jika ada masalah** → perbaiki → ulangi langkah 3–5 sampai lolos.
7. **Cek error kode** (`get_errors`) setelah setiap edit file.

Catatan:

- Sesi login bisa berakhir saat reload — jika halaman redirect ke `/login`, minta kredensial test ke user atau minta user login dulu.
- Verifikasi juga dark mode bila perubahan menyentuh warna/border.
- Untuk perubahan responsive, cek minimal desktop + viewport sempit.

### Git Rules

- Do **not** commit changes without explicit user permission.
- Do **not** create commits automatically after completing a task.

### Editing Restriction

- Do **not** use `sed -i` or any equivalent in-place `sed` editing command.
