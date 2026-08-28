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

| #   | Fitur             | Deskripsi                                                                                                                                                                                              |
| --- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| F1  | Auth              | Register, login, logout via email+password                                                                                                                                                             |
| F2  | Form CV           | Data pribadi, ringkasan, pengalaman kerja (bullet per baris), pendidikan (IPK opsional), organisasi (max 5), hard/soft skills, bahasa, sertifikat, proyek terstruktur (title/role/objective/techStack) |
| F3  | Preview real-time | Render CV saat form diisi, 2 template (modern, classic)                                                                                                                                                |
| F4  | Simpan draft      | CRUD CV per user; satu user punya banyak CV                                                                                                                                                            |
| F5  | AI summary        | Generate ringkasan profesional dari data form (Gemini), bisa diedit manual                                                                                                                             |
| F6  | Download PDF      | Server-side render HTML → PDF, nama file `{nama}_CV.pdf`                                                                                                                                               |
| F7  | Multi-bahasa CV   | Konten CV ID/EN (pilihan user per CV)                                                                                                                                                                  |

## 5. Non-Fitur v1 (dilarang dibangun sekarang)

- ❌ Template premium / paywall
- ❌ Upload foto profil
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

- [ ] User bisa register → login → buat CV → preview → download PDF.
- [ ] Draft tersimpan dan bisa dibuka lagi dari device lain.
- [ ] AI summary menghasilkan teks relevan ≤ 5 detik.
- [ ] PDF identik dengan preview.
