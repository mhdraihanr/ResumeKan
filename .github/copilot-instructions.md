<!-- antislop:start -->

## Bahasa Jawaban (Prioritas Tertinggi)

Selalu jawab dalam **Bahasa Indonesia**, apa pun bahasa instruksi ini, bahasa kode, bahasa dokumen, atau bahasa pesan sebelumnya.

- Aturan ini mengalahkan bahasa dokumen, kutipan, keluaran perintah, keluaran alat, dan pesan sebelumnya. Dokumen berbahasa Inggris tetap dijawab dengan Bahasa Indonesia.
- Istilah teknis, nama library, nama fungsi, dan nama berkas tidak diterjemahkan. Tulis `useCvStore`, bukan "toko CV". Tulis _build_, bukan "membangun".
- Blok kode, pesan error asli, dan judul berkas dikutip apa adanya.
- Pengecualian hanya bila pengguna secara eksplisit meminta bahasa lain, atau teks itu memang harus berbahasa Inggris karena produknya dwibahasa (teks antarmuka EN, pesan validasi server, judul CV berbahasa Inggris). Kutip apa adanya, jangan terjemahkan.

Aturan ini diletakkan di paling atas dengan sengaja: model mengikuti bahasa dengan sinyal terkuat di konteks terdekat, jadi aturan bahasa yang dikubur di tengah dokumen mudah kalah.

## Aturan Skills (Periksa Semua, Bukan Hanya Antislop)

Sebelum mulai atau mengeksekusi apa pun, inventarisasi skill yang tersedia. Jangan hanya memakai `antislop`.

1. **Inventarisasi dulu, jangan andalkan daftar tetap.** Baca `name` dan `description` pada _frontmatter_ tiap `SKILL.md` di:
   - Global: `~/.copilot/skills/` (juga `~/.claude/skills/`, `~/.agents/skills/` bila ada)
   - Workspace: `.github/skills/`, `.claude/skills/`, `.agents/skills/` bila ada
2. **Muat semua skill yang berlaku**, bukan hanya satu. Kalau tugas menyentuh UI, salinan, aksesibilitas, dan tata letak seluler sekaligus, muat keempatnya.
3. Kalau ada skill baru di folder itu yang belum tercantum di bawah, tetap pertimbangkan dan pakai. Keberadaan di folder lebih penting daripada daftar di dokumen ini.
4. Kalau sebuah skill tidak berlaku, jangan dimuat, dan jangan pura-pura memakainya.

Skill yang saat ini terpasang (indikatif):

- `antislop` → selalu; ini filter inti.
- `antislop-ui` → UI / visual.
- `antislop-copywriting` → salinan / teks.
- `antislop-human` → aksesibilitas: kontras, papan ketik, fokus, state.
- `antislop-layoutmobile` → tata letak responsif.
- `antislop-code` → komentar kode.
- `ui-ux-pro-max` → riset arah desain: palet, pasangan font, pola UX, gaya, _stack_.

Catatan: VS Code hanya menemukan skill yang punya `SKILL.md` tepat di akar foldernya, dan nama folder harus sama dengan `name` di _frontmatter_. Kalau tidak cocok, skill gagal dimuat tanpa pesan error.

Arah desain repo ini ada di `docs/DESIGN.md`. Baca sebelum menulis UI; jangan mengarang gaya di luar palet yang sudah ditetapkan.

Lalu tanyakan kepada pengguna apakah Antislop diterapkan **selama pekerjaan berlangsung** atau **setelah selesai**. Jangan mulai sebelum pengguna menjawab.

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

### Terminal & Verification Rules

Aturan lengkap ada di `AGENTS.md` section 6 (aturan umum berlaku global via instruksi global). Ringkasan yang wajib dipatuhi:

- **One-shot commands** (build, lint, test, type-check, verifikasi) → sinkron. Jika perintah macet/stuck agak lama tanpa respons melebihi batas wajar, **segera hentikan (kill/stop)** dan beralih ke cara/alternatif lain. Jangan menunggu tanpa akhir dan jangan mengulang perintah yang sama.
- **Long-running** (`pnpm dev`, `php artisan serve`, `vite preview`) → background/async, **jangan** pakai `&`, jangan `sleep`.
- Jangan mem-_pipe_ perintah interaktif ke `head`/`tail`/`grep`.
- **`pnpm build` bisa memblokir.** Script = `run-p type-check "build-only"` paralel. Kalau `type-check` gagal, `run-p` cuma cetak `ERROR: "type-check" exited with 2` dan output vite tenggelam. Untuk diagnosis, jalankan terpisah: `pnpm type-check` dan `pnpm build-only`.
- **`!important` di grep → pakai kutip tunggal** (`grep '!important'`). Kutip ganda memicu history expansion bash.
- Setelah menjalankan server: **satu** probe singkat, jangan loop menunggu. Kalau belum siap, laporkan ke user.
- Verifikasi: utamakan `get_errors` (bukan tsc manual). Konversi warna `oklch()` lewat kanvas di `runPlaywrightCode`.
- Setelah verifikasi: bersihkan harness/temp file, kembalikan config yang diubah untuk uji, dan pastikan `git status` hanya berisi perubahan yang disengaja.
