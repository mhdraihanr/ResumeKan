# Panduan Agen AI (AGENTS.md)

Dokumen ini mendefinisikan aturan perilaku, batasan, dan alur verifikasi yang wajib diikuti oleh semua Agen AI (seperti GitHub Copilot, Cursor, dsb.) saat beroperasi di dalam repositori ini.

## 0. Bahasa Jawaban (Prioritas Tertinggi)

**Selalu jawab dalam Bahasa Indonesia**, apa pun bahasa instruksi ini, bahasa kode, bahasa dokumen, atau bahasa pesan sebelumnya.

- Aturan ini mengalahkan bahasa dokumen, kutipan, keluaran perintah, keluaran alat, dan pesan sebelumnya. Kalau dokumen atau pesan itu berbahasa Inggris, jawaban tetap Bahasa Indonesia.
- Istilah teknis, nama _library_, nama fungsi, dan nama berkas **tidak diterjemahkan**. Contoh: tulis `useCvStore`, bukan "toko CV"; tulis _build_, bukan "membangun".
- Blok kode, nama _commit_, dan pesan _error_ asli dikutip apa adanya.
- Satu-satunya pengecualian: pengguna secara eksplisit meminta jawaban dalam bahasa lain.
- Pengecualian kedua: kalimat yang **memang** harus berbahasa Inggris karena produknya dwibahasa (mis. teks antarmuka berbahasa Inggris, pesan validasi server, judul CV berbahasa Inggris). Kutip apa adanya, jangan terjemahkan.
- Ringkasan akhir, laporan verifikasi, dan penjelasan ke pengguna selalu Bahasa Indonesia.

Alasan aturan ini ditulis di paling atas: model cenderung mengikuti bahasa dengan sinyal terkuat di konteks terdekat (isi berkas, keluaran perintah, pesan pengguna). Aturan bahasa yang dikubur di tengah dokumen mudah kalah dari sinyal itu, jadi aturan ini harus berada di awal dan menyatakan bahwa ia mengalahkan sinyal lain.

## 1. Aturan Skills (Wajib Periksa Semua)

Sebelum memulai atau mengeksekusi **apa pun**, periksa skill yang tersedia, bukan hanya `antislop`.

### 1.1 Selalu inventarisasi dulu

Jangan mengandalkan daftar tetap di dalam dokumen ini. Daftar itu bisa basi. Langkah yang benar:

1. Baca folder skill yang relevan untuk mengetahui isi terbaru:
   - Global: `~/.copilot/skills/` (juga `~/.claude/skills/`, `~/.agents/skills/` bila ada)
   - Workspace: `.github/skills/`, `.claude/skills/`, `.agents/skills/` bila ada
2. Baca bagian `name` dan `description` pada _frontmatter_ tiap `SKILL.md`. Deskripsi itulah yang menentukan apakah skill berlaku untuk tugas yang sedang dikerjakan.
3. Muat **semua skill yang berlaku**, bukan hanya satu. Kalau tugas menyentuh UI, salinan, aksesibilitas, dan tata letak seluler sekaligus, muat keempatnya.
4. Kalau ada skill baru di folder itu yang belum tercantum di bawah, tetap pertimbangkan dan pakai. Keberadaannya di folder lebih penting daripada daftar di dokumen ini.
5. Kalau sebuah skill **tidak** berlaku, jangan dimuat, dan jangan pura-pura memakainya.

### 1.2 Skill yang saat ini terpasang (indikatif, bukan pengganti langkah 1.1)

| Skill                   | Muat saat                                                        |
| ----------------------- | ---------------------------------------------------------------- |
| `antislop`              | Selalu. Ini filter inti.                                         |
| `antislop-ui`           | UI / visual: warna, tata letak, komponen, dekorasi, gerak.       |
| `antislop-copywriting`  | Salinan / teks: judul, CTA, nada, prosa produk.                  |
| `antislop-human`        | Aksesibilitas: kontras, papan ketik, fokus, state.               |
| `antislop-layoutmobile` | Tata letak responsif: breakpoint, grid, luapan, target sentuh.   |
| `antislop-code`         | Komentar kode.                                                   |
| `ui-ux-pro-max`         | Riset arah desain: palet, pasangan font, pola UX, gaya, _stack_. |

Catatan pemasangan: `ui-ux-pro-max` disalin dari repo `ui-ux-pro-max-skill`, tetapi VS Code hanya menemukan skill yang punya `SKILL.md` tepat di akar foldernya. Pastikan foldernya bernama sama dengan `name` di _frontmatter_, kalau tidak skill itu gagal dimuat tanpa pesan error.

### 1.3 Urutan kerja

1. Inventarisasi skill (langkah 1.1) dan muat semua yang berlaku.
2. Tanyakan kepada pengguna apakah Antislop diterapkan **selama pekerjaan berlangsung** atau **setelah selesai**.
3. Jangan mengeksekusi perintah, memodifikasi berkas, atau memulai implementasi sebelum skill yang berlaku dimuat dan pengguna sudah menjawab.

### 1.4 Arah desain sebelum membangun

Untuk pekerjaan UI, `antislop` mewajibkan arah desain disepakati lebih dulu. Di repo ini arah itu ada di `docs/DESIGN.md` (palet, dials, tipografi, aturan komponen). Baca dokumen itu sebelum menulis UI, dan jangan mengarang gaya baru di luar palet yang sudah ditetapkan.

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

- **Perintah one-shot** (build, lint, test, type-check, verifikasi): jalankan sinkron. Jika perintah berisiko menggantung atau berjalan tanpa kemajuan melebihi batas waktu wajar, pasang batas pengaman (safety timeout).
- **Proses long-running** (`pnpm dev`, `php artisan serve`, `vite preview`): jalankan sebagai proses background/async, **jangan** pakai `&` di dalam shell.
- Jangan mem-_pipe_ perintah interaktif ke `head`/`tail`/`grep`.
- Jangan menyalurkan secret ke terminal.

### 6.2 Penanganan Terminal Stuck / Menggantung

- **Hentikan Segera jika Stuck**: Jika terminal macet atau stuck agak lama (tidak ada respons atau kemajuan di luar batas waktu wajar, menunggu prompt tersembunyi, timeout, atau deadlock proses): **segera hentikan (kill/stop/cancel)** perintah tersebut. Jangan biarkan terminal menggantung tanpa akhir.
- **Beralih ke Cara Lain**: Setelah menghentikan proses yang stuck, **jangan mengulang perintah identik yang sama**. Beralihlah ke pendekatan alternatif:
  - Utamakan tool diagnostik internal (mis. `get_errors`) atau pembacaan file/inspeksi browser dibanding compiler CLI berat.
  - Pecah perintah kompleks (mis. jika `pnpm build` macet, jalankan terpisah `pnpm type-check` dan `pnpm build-only`).
  - Gunakan alternatif yang lebih ringan (mis. Node/Python inline script atau uji per-komponen).
  - Jika kendala tetap memerlukan tindakan manual, laporkan ringkas ke pengguna dan lanjutkan tugas yang masih bisa dikerjakan.

### 6.3 Jebakan spesifik project ini

- **`pnpm build` bisa memblokir.** Script-nya adalah `run-p type-check "build-only"` yang berjalan paralel. Jika `type-check` gagal, `run-p` hanya mencetak `ERROR: "type-check" exited with 2` dan output vite tenggelam sehingga tampak seperti menggantung. Untuk diagnosis, jalankan terpisah: `pnpm type-check` lalu `pnpm build-only`.
- **Tailwind v4 memakai `!important` di CSS.** Saat mencari pola itu dengan `grep`, gunakan kutip tunggal (`grep '!important'`). Kutip ganda memicu history expansion bash dan menghasilkan `event not found`.
- **Menghitung em dash per baris.** Jangan pakai `grep -c` atau `wc -l`; grep bekerja lintas baris. Gunakan skrip singkat bila perlu menghitung per baris. Konvensi project: em dash `—` hanya untuk pemisah tanggal pada entri changelog, bukan di prosa.
- **Warna Tailwind v4 ter-emit sebagai `oklch()`.** Konversi ke hex lewat round-trip kanvas 2D di browser, jangan parsing string secara manual.
- **Verifikasi PDF butuh harness.** Jalur produksi ada di `CvController::resolvePrintHtml` + `PdfService::render` (memerlukan auth). Untuk verifikasi tanpa auth, buat route sementara, lalu **hapus route dan kembalikan `api/bootstrap/app.php`** setelah selesai. Jangan tinggalkan harness di working tree.
- **Jangan pakai `git stash --include-untracked` tanpa cek `git status` setelahnya** — pernah menghilangkan `AGENTS.md` dari working tree.

### 6.4 Menunggu server siap

- Setelah menjalankan server, jangan menunggu dengan `sleep` atau loop. Lakukan **satu** probe singkat (mis. satu `curl` ke `/up`).
- Jika belum siap, laporkan ke pengguna, jangan mencoba berulang kali.

### 6.5 Setelah verifikasi

- Laporkan exit code dan ringkasan hasil, bukan hanya klaim "berhasil".
- Bersihkan file sementara dan harness verifikasi, lalu pastikan `git status` hanya memuat perubahan yang disengaja.
