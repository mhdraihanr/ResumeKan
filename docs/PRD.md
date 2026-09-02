# PRD — ResumeKan

> Product Requirements Document. Menjawab **apa** yang dibangun, bukan bagaimana.

## 1. Ringkasan

ResumeKan adalah web app yang membantu pencari kerja (fokus: Indonesia) membuat CV ATS-friendly dalam hitungan menit. User mengisi form terstruktur, AI membantu menulis ringkasan profesional, lalu user memilih template dan mendownload PDF.

## 2. Masalah

- Membuat CV dari nol butuh waktu & skill desain.
- Banyak CV ditolak ATS sebelum dibaca manusia.
- Solusi existing (Applyin asli) tidak menyimpan data — refresh = hilang.

## 3. Target User

1. Fresh graduate / mahasiswa tingkat akhir yang melamar kerja-magang.
2. Pekerja yang ingin memperbarui CV dengan cepat.

## 4. Fitur MVP (v1)

| #   | Fitur             | Deskripsi                                                                                                                                                                                                                                                                                                                           |
| --- | ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F1  | Auth              | Register, login, logout via email+password                                                                                                                                                                                                                                                                                          |
| F2  | Form CV           | Data pribadi (termasuk foto profil opsional via upload Cloudinary), ringkasan, pengalaman kerja (employment type Full-time/Part-time/Internship, bullet per baris), pendidikan (IPK opsional), organisasi (max 5), hard/soft skills, bahasa, sertifikat, proyek terstruktur (title/role/objective/techStack)                        |
| F3  | Preview real-time | Render CV saat form diisi, 3 template (modern, classic, neon)                                                                                                                                                                                                                                                                       |
| F4  | Simpan draft      | CRUD CV per user; satu user punya banyak CV; tombol `Simpan Draft` di editor menyimpan tanpa keluar form (toast konfirmasi)                                                                                                                                                                                                         |
| F5  | AI summary        | Generate ringkasan profesional fokus posisi dominan/terbaru dari data form (proyek hanya konteks tech, tidak dijelaskan) via AI gateway OpenAI-compatible, bisa diedit manual                                                                                                                                                       |
| F6  | Download PDF      | Server-side render HTML → PDF, nama file `{nama}_CV.pdf`                                                                                                                                                                                                                                                                            |
| F7  | Multi-bahasa CV   | Konten CV ID/EN (pilihan user per CV). `language` (`id`/`en`) menyimpan pilihan; judul/label section dokumen mengikuti bahasa via `cv-labels.ts`, konten user tidak diterjemahkan (heading ID/EN saja, mulai 2026-09-02). Konten ID→EN (duplikat & terjemahkan) masuk phase-7 (7b, ✅ selesai 2026-09-02 via Google gtx), bukan F7. |

## 5. Non-Fitur v1 (dilarang dibangun sekarang)

- ❌ Template premium / paywall
- ❌ Share link publik CV
- ❌ Blog CMS (landing statis saja)
- ❌ OAuth Google/LinkedIn
- ❌ ATS score checker

## 6. Aturan Bisnis

- Satu user maksimal **10 CV** aktif.
- Endpoint AI di-rate-limit **5 request/menit/user**.
- Data CV milik user; tidak ada akses antar-user.
- Delete CV bersifat permanen (tanpa trash) di v1.

## 7. Kriteria Selesai MVP

- [x] User bisa register → login → buat CV → preview → download PDF.
- [x] Draft tersimpan dan bisa dibuka lagi dari device lain.
- [x] AI summary menghasilkan teks relevan ≤ 5 detik.
- [x] PDF identik dengan preview.
