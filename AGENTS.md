# Panduan Agen AI (AGENTS.md)

Dokumen ini mendefinisikan aturan perilaku, batasan, dan alur verifikasi yang wajib diikuti oleh semua Agen AI (seperti GitHub Copilot, Cursor, dsb.) saat beroperasi di dalam repositori ini.

## 1. Aturan Antislop

Sebelum memulai atau mengeksekusi **apa pun**, periksa apakah Antislop berlaku.

Jika berlaku:

1. Tanyakan kepada pengguna apakah Antislop harus diterapkan **selama pekerjaan berlangsung** atau **setelah selesai**.
2. Sebelum implementasi, muat keahlian (_skills_) yang diperlukan dari `~/.copilot/skills/`:

- `antislop` → Selalu diperlukan
- `antislop-ui` → UI / visual
- `antislop-copywriting` → Salinan (_copy_) / teks
- `antislop-human` → Manusia (_people_)
- `antislop-layoutmobile` → Seluler (_mobile_) / responsif
- `antislop-code` → Komentar kode

3. Muat **semua keahlian yang berlaku** sebelum melakukan pekerjaan apa pun.
4. Jangan mengeksekusi perintah, memodifikasi file, atau memulai implementasi sebelum keahlian yang diperlukan dimuat dan pengguna telah memberikan jawaban.

## 2. Aturan Pengujian

- Saat pengujian diperlukan, **prioritaskan pengujian melalui GitHub Copilot Web di VS Code**.
- Gunakan metode pengujian alternatif hanya jika Copilot Web di VS Code tidak tersedia atau tidak sesuai.
- Jangan gunakan alat atau metode pengujian eksternal kecuali diinstruksikan secara eksplisit oleh pengguna.

## 3. Aturan Verifikasi UI (Browser Tools)

Setiap perubahan UI **wajib diverifikasi lewat browser terintegrasi VS Code** (_browser tools_), bukan hanya dari kode. Ikuti loop tertutup ini:

1. **Edit kode** → pastikan _dev server_ berjalan (`pnpm dev` di `web/`, default `http://localhost:5173`).
2. **Buka/navigasi** halaman target di browser terintegrasi (`openBrowserPage` / `navigatePage`). Jika pengguna sudah membagikan tab, gunakan tab tersebut — jangan buka tab baru.
3. **Periksa hasil** dengan minimal dua dari:

- `readPage` — _snapshot_ aksesibilitas: struktur, _heading_, tombol, dan teks yang benar.
- `screenshotPage` — verifikasi visual: tata letak (_layout_), spasi, warna, mode gelap (_dark mode_).
- `runPlaywrightCode` — untuk pengecekan yang butuh skrip: posisi _sticky_ saat digulir (_scroll_), ukuran elemen, status interaktif.

4. **Interaksi bila relevan** — klik _stepper_, isi formulir, kirim (_submit_), periksa _toast_/error (`clickElement`, `typeInPage`).
5. **Laporkan hasil** — sebutkan apa yang diuji dan hasilnya (mis. "bar sticky terkonfirmasi di top: 0 saat di-scroll, 0 error").
6. **Jika ada masalah** → perbaiki → ulangi langkah 3–5 sampai lolos.
7. **Periksa error kode** (`get_errors`) setelah setiap pengeditan file.

**Catatan Verifikasi UI:**

- Sesi login bisa berakhir saat dimuat ulang (_reload_) — jika halaman dialihkan (_redirect_) ke `/login`, minta kredensial pengujian ke pengguna atau minta pengguna login terlebih dahulu.
- Verifikasi juga mode gelap (_dark mode_) bila perubahan menyentuh warna/batas (_border_).
- Untuk perubahan responsif, periksa minimal tampilan desktop + _viewport_ sempit.

## 4. Aturan Git

- **Jangan** melakukan _commit_ perubahan tanpa izin eksplisit dari pengguna.
- **Jangan** membuat _commit_ secara otomatis setelah menyelesaikan sebuah tugas.

## 5. Batasan Pengeditan

- **Jangan** menggunakan `sed -i` atau perintah pengeditan _in-place_ `sed` lainnya yang setara.

## 6. Aturan Terminal & Verifikasi Perintah

Tujuan: tidak ada perintah yang menggantung, menunggu input, atau memblokir terminal. Aturan umum berlaku global; bagian ini menambahkan hal spesifik project ini.

### 6.1 Mode eksekusi

- **Perintah one-shot** (build, lint, test, type-check, verifikasi): jalankan sinkron dan tunggu sampai selesai. Jangan diberi timeout buatan dan jangan dijalankan di background.
- **Proses long-running** (`pnpm dev`, `php artisan serve`, `vite preview`): jalankan sebagai proses background/async, **jangan** pakai `&` di dalam shell.
- Jangan mem-_pipe_ perintah interaktif ke `head`/`tail`/`grep`.
- Jangan menyalurkan secret ke terminal.

### 6.2 Jebakan spesifik project ini

- **`pnpm build` bisa memblokir.** Script-nya adalah `run-p type-check "build-only"` yang berjalan paralel. Jika `type-check` gagal, `run-p` hanya mencetak `ERROR: "type-check" exited with 2` dan output vite tenggelam sehingga tampak seperti menggantung. Untuk diagnosis, jalankan terpisah: `pnpm type-check` lalu `pnpm build-only`.
- **Tailwind v4 memakai `!important` di CSS.** Saat mencari pola itu dengan `grep`, gunakan kutip tunggal (`grep '!important'`). Kutip ganda memicu history expansion bash dan menghasilkan `event not found`.
- **Menghitung em dash per baris.** Jangan pakai `grep -c` atau `wc -l`; grep bekerja lintas baris. Gunakan skrip singkat bila perlu menghitung per baris. Konvensi project: em dash `—` hanya untuk pemisah tanggal pada entri changelog, bukan di prosa.
- **Warna Tailwind v4 ter-emit sebagai `oklch()`.** Konversi ke hex lewat round-trip kanvas 2D di browser, jangan parsing string secara manual.
- **Verifikasi PDF butuh harness.** Jalur produksi ada di `CvController::resolvePrintHtml` + `PdfService::render` (memerlukan auth). Untuk verifikasi tanpa auth, buat route sementara, lalu **hapus route dan kembalikan `api/bootstrap/app.php`** setelah selesai. Jangan tinggalkan harness di working tree.
- **Jangan pakai `git stash --include-untracked` tanpa cek `git status` setelahnya** — pernah menghilangkan `AGENTS.md` dari working tree.

### 6.3 Menunggu server siap

- Setelah menjalankan server, jangan menunggu dengan `sleep` atau loop. Lakukan **satu** probe singkat (mis. satu `curl` ke `/up`).
- Jika belum siap, laporkan ke pengguna, jangan mencoba berulang kali.

### 6.4 Setelah verifikasi

- Laporkan exit code dan ringkasan hasil, bukan hanya klaim "berhasil".
- Bersihkan file sementara dan harness verifikasi, lalu pastikan `git status` hanya memuat perubahan yang disengaja.
