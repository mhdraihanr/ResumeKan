# Panduan Agen AI (AGENTS.md)

Dokumen ini mendefinisikan aturan perilaku, batasan, dan alur verifikasi yang wajib diikuti oleh semua Agen AI (seperti GitHub Copilot, Cursor, dsb.) saat beroperasi di dalam repositori ini.

## 1. Aturan Antislop

Sebelum memulai atau mengeksekusi **apa pun**, periksa apakah Antislop berlaku.

Jika berlaku:

1. Tanyakan kepada pengguna apakah Antislop harus diterapkan **selama pekerjaan berlangsung** atau **setelah selesai**.
2. Sebelum implementasi, muat keahlian (_skills_) yang diperlukan dari `.github/skills/`:

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
