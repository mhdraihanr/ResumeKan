# Fase 7a — Import CV dari PDF/DOCX → auto-isi form

> Status: **Selesai** (T1 ✅, T2 ✅, T3 ✅, T4 ✅, T5 ✅, T6 ✅, T6b ✅, T7 ✅, T7b ✅, T8 ✅) · Estimasi: 1–1.5 hari · Prasyarat: Fase 4 (AiService) ✅ + Fase 5 (PDF) ✅
> Induk backlog: [Fase 7 — Post-MVP](phase-7-post-mvp.md) §7a. Dokumen ini memecah §7a menjadi task yang bisa dieksekusi.

## Tujuan

User yang sudah punya CV tidak perlu mengetik ulang. Ia upload PDF/DOCX, server mengekstrak teks lalu meminta AI menyusunnya ke bentuk `CvData`, hasilnya mengisi form. Tidak ada auto-save: user mereview dan mengedit dulu sebelum menyimpan.

## Batasan v1 (dari backlog, tidak dinegosiasi di sini)

- Maks 5 MB, maks 5 halaman.
- PDF text-based saja. Scan/gambar ditolak dengan pesan jelas.
- Throttle 3 request/menit per user.
- File tidak disimpan ke disk permanen. Dibaca, dipakai, dihapus.
- `502` bila layanan AI gagal, `422` bila file tidak valid, `429` bila lewat throttle.
- Hasil mengisi form lokal saja, tidak menulis DB.

## Keputusan terbuka (wajib diputus sebelum T1)

Tiga hal di bawah belum diputus di backlog. Anggap ini gerbang, bukan detail. **Ketiganya sudah diputus 2026-10-06** — rekomendasi di bawah dipakai.

1. **Dukungan DOCX.** `smalot/pdfparser` hanya menangani PDF. DOCX butuh jalur terpisah. Dua opsi:
   - **Opsi A (tanpa dependency baru):** baca `word/document.xml` di dalam DOCX memakai ekstensi `zip` PHP yang sudah ada, buang tag XML. Cukup untuk teks paragraf. Butuh `ext-zip` (biasanya sudah aktif).
   - **Opsi B (dependency baru):** `phpoffice/phpword`. Lebih tahan format, menambah bobot `composer.json`.
   - **Rekomendasi:** Opsi A untuk v1. Naik ke Opsi B hanya kalau DOCX yang masuk sering rusak.
   - ✅ **Diputus: Opsi A.**
2. **Bentuk output AI.** `{ "data": { ...CvData } }` plus daftar field kosong, atau hanya `CvData` polos. **Rekomendasi:** sertakan `warnings: string[]` agar FE bisa memberi tahu field yang gagal diisi.
   - ✅ **Diputus: `{ data, warnings }`.**
3. **Model AI untuk strukturisasi.** Model yang sama dengan `AI_MODEL` atau model terpisah. **Rekomendasi:** model yang sama dulu (satu env, tanpa konfigurasi baru). Pecah hanya kalau kualitas JSON jelek.
   - ✅ **Diputus: model yang sama (`AI_MODEL`).**

## Peta dependensi

```
DocumentTextExtractor (ekstraksi teks)        ← tidak butuh AI, bisa diuji sendiri
        │
        ├── AiService::structureCvData()      ← ubah teks → CvData JSON
        │           │
        │           └── POST /ai/parse-cv     ← AiController@parseCv + Form Request
        │                       │
        │                       └── web/src/api/cv.ts (parseCv, multipart)
        │                                   │
        │                                   └── ImportCvPanel.vue → CvFormView
        └── Rate limiter `ai-parse` (3/menit)
```

Urutan eksekusi mengikuti graf dari bawah: ekstraksi dulu, lalu AI, lalu endpoint, terakhir UI.

---

## Task 1: Rate limiter + config untuk parse

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Tambah limiter bernama `ai-parse` (3/menit per user) mengikuti pola limiter `ai` yang sudah ada, plus entri config agar angkanya bisa diubah lewat `.env`.

**Acceptance criteria:**

- `RateLimiter::for('ai-parse', ...)` ada di `AppServiceProvider::boot()`, per user (fallback IP), nilai dari `config('ai.parse_throttle_per_minute', 3)`.
- `config/ai.php` punya kunci `parse_throttle_per_minute` dari env `AI_PARSE_THROTTLE_PER_MINUTE`.
- `.env.example` diperbarui.

**Verifikasi:**

- [x] `./vendor/bin/phpunit --filter AiParseRateLimiterTest` lulus (5 test, 6 assertion).
- [x] `./vendor/bin/phpunit` seluruh suite lulus (43 test, 121 assertion) — tidak ada regresi.
- [x] `config('ai.parse_throttle_per_minute')` = 3, limiter `ai-parse` terdaftar (dicek via `Artisan::call` + `RateLimiter::limiter()`).

**Dependencies:** None

**Files touched:**

- `api/app/Providers/AppServiceProvider.php`
- `api/config/ai.php`
- `api/.env.example`
- `api/tests/Unit/AiParseRateLimiterTest.php` (baru)

**Estimated scope:** Small (3 files + 1 test)

---

## Task 2: DocumentTextExtractor (ekstraksi teks)

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Service murni yang mengubah file upload jadi teks. Tanggung jawabnya tunggal: keluar teks atau lempar exception yang jelas. Tidak menyentuh AI, tidak menyentuh DB. Ini bagian yang bisa diuji tanpa jaringan.

**Acceptance criteria:**

- `extract(UploadedFile $file): array` mengembalikan `['text' => string, 'pages' => int]`.
- PDF: pakai `smalot/pdfparser`. Tolak bila `pages > 5`.
- DOCX: sesuai keputusan #1 (Opsi A: `ZipArchive` + `word/document.xml`).
- Deteksi PDF scan/gambar: bila teks hasil ekstraksi kosong atau di bawah ambang (mis. 100 karakter), lempar exception `UnreadableDocumentException` dengan pesan "File tidak berisi teks yang bisa dibaca. Upload PDF text-based, bukan hasil scan."
- PDF terenkripsi / rusak: tangkap exception pdfparser, lempar exception yang sama.
- Tidak menulis file permanen. Kalau perlu file sementara, hapus di `finally`.

**Verifikasi:**

- [x] `./vendor/bin/phpunit --filter DocumentTextExtractorTest` lulus (9 test, 16 assertion).
- [x] Fixture PDF text-based kecil mengekstrak teks non-kosong dan menghitung halaman benar.
- [x] Fixture PDF tanpa teks (atau PDF kosong) memicu exception pesan scan.
- [x] `./vendor/bin/phpunit` seluruh suite lulus (52 test, 137 assertion) — tidak ada regresi.

**Catatan implementasi:**

- `pages` bernilai `null` untuk DOCX (jumlah halaman DOCX baru ditentukan saat dirender, tidak tersimpan di berkasnya). Kontrak tetap `int|null`.
- Fixture PDF/DOCX dibangun sebagai byte mentah di dalam test (helper `pdfBytes()`, `docxBytes()`), jadi repo tidak menyimpan berkas biner dan `api/tests/Fixtures/` tidak dipakai.
- Ditambah guard zip bomb: `word/document.xml` > 2 MB ditolak berdasarkan ukuran di direktori zip (sebelum ekstraksi), supaya arsip yang memuai besar tidak menghabiskan memori.

**Dependencies:** None (keputusan #1 harus sudah diputus)

**Files touched:**

- `api/app/Services/DocumentTextExtractor.php` (baru)
- `api/app/Exceptions/UnreadableDocumentException.php` (baru)
- `api/composer.json` + `api/composer.lock` (tambah `smalot/pdfparser ^2.12`)
- `api/tests/Unit/DocumentTextExtractorTest.php` (baru)

**Estimated scope:** Medium (4–5 files)

---

## Task 3: AiService::structureCvData (teks → CvData)

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Tambah metode di `AiService` yang mengubah teks CV mentah jadi array `CvData` terstruktur, memakai gateway OpenAI-compatible yang sudah ada. Keluar array atau lempar `RuntimeException` seperti `generateSummary()`.

**Acceptance criteria:**

- `structureCvData(string $text, string $language = 'id'): array` mengembalikan array yang bentuknya cocok dengan `CvData` (lihat `DATA_MODEL.md`).
- Prompt melarang mengarang: hanya pakai fakta dari teks. Field yang tidak ada diisi kosong, tidak diisi tebakan.
- Prompt minta JSON ketat. Ekstraksi JSON tahan banting: buang code fence, ambil objek `{...}` pertama yang valid.
- Teks input dipotong (mis. 15000 karakter) supaya tidak melewati batas token.
- Bila `AI_API_KEY` kosong → lempar `RuntimeException('AI_API_KEY belum diisi')` (pola sama dengan `generateSummary`).
- Bila respons tidak bisa diparse jadi JSON valid → lempar `RuntimeException`.

**Verifikasi:**

- [x] `./vendor/bin/phpunit --filter AiServiceStructureTest` lulus (13 test, 29 assertion, pakai `Http::fake()`).
- [x] Respons JSON valid → array benar.
- [x] Respons dibungkus code fence → tetap terparse.
- [x] Respons sampah → exception.
- [x] `./vendor/bin/phpunit` seluruh suite lulus (65 test, 166 assertion) — tidak ada regresi.

**Catatan implementasi:**

- Hasil dinormalisasi ke bentuk `CvData` sebelum dikembalikan: semua kunci selalu ada, tipe dipaksa string/array, kunci asing dibuang. Ini yang membuat T4 nanti cukup memanggil `structureCvData()` tanpa pembersihan tambahan.
- Respons SSE (`data: {...}` + `delta.content`) ikut ditangani — gateway lokal (`AI_BASE_URL`) membalas SSE walau diminta JSON, jadi tanpa cabang ini import selalu gagal.
- Model sering membalas `[{...}]`; hanya objek (bukan list) yang diterima langsung, objek di dalam array tetap diambil lewat pemindaian kurung.
- `employmentType` diselaraskan ke nilai `in:` milik `StoreCvRequest` (mis. "Full Time" → "Full-time"); varian tak dikenal jadi string kosong.
- Jumlah entri dipotong sesuai batas `StoreCvRequest` (pengalaman 10, pendidikan/skill/sertifikat/organisasi 5, proyek 8) supaya simpan tidak gagal validasi.
- `set_time_limit(120)` (bukan 60 seperti ringkasan) karena ekstraksi file + AI lebih lama.

**Dependencies:** None (bisa paralel dengan T2)

**Files touched:**

- `api/app/Services/AiService.php`
- `api/tests/Unit/AiServiceStructureTest.php` (baru)

**Estimated scope:** Medium (2 files, logika parsing agak panjang)

---

## Task 4: Endpoint POST /ai/parse-cv + test Feature

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Sambungkan T2 dan T3 jadi satu endpoint. Validasi request, cek throttle, panggil extractor, panggil AI, balas `{ data, warnings }`.

**Acceptance criteria:**

- Route `POST /api/v1/ai/parse-cv` di dalam grup `auth:sanctum`, middleware `throttle:ai-parse`, diarahkan ke `AiController@parseCv`.
- Validasi: `file` wajib, `File::types(['pdf','docx'])->max(5 * 1024)` (5 MB).
- Tanpa auth → `401`. File tidak valid/terlalu besar → `422`. Lewat throttle → `429` dengan header `Retry-After`.
- Ekstraksi gagal (scan/rusak) → `422` dengan pesan Indonesia yang jelas.
- `AI_API_KEY` kosong → `503 AI belum dikonfigurasi`. AI down → `502 AI service unavailable` (konsisten dengan `summary`).
- Sukses → `200 { data: CvData, warnings: string[] }`. `warnings` berisi nama field yang kosong.
- File tidak ditinggalkan di disk.

**Verifikasi:**

- [x] `./vendor/bin/phpunit --filter AiParseCvTest` lulus (14 test, 40 assertion, semua status di atas).
- [x] `route:list` menampilkan `POST api/v1/ai/parse-cv` → `AiController@parseCv` dengan middleware `api, Authenticate:sanctum, ThrottleRequests:ai-parse`.
- [x] `curl` tanpa auth → `HTTP/1.1 401 Unauthorized`.
- [x] `./vendor/bin/phpunit` seluruh suite lulus (79 test, 206 assertion) — tidak ada regresi.

**Catatan implementasi:**

- Fixture DOCX di test harus **realistis**: zip minimal tanpa `[Content_Types].xml` terdeteksi sebagai `application/zip` dan ditolak `File::types(['docx'])`. Fixture menyertakan `<Override PartName="/word/document.xml">` supaya MIME-nya benar.
- Pesan `file.uploaded` sengaja **tanpa angka** (bukan "maksimal 5 MB"): `UPLOAD_ERR_INI_SIZE` bisa muncul saat PHP menolak berkas sebelum validasi Laravel jalan, sehingga menyebut "5 MB" akan menyesatkan. Ada test khusus untuk jalur ini (`UPLOAD_ERR_INI_SIZE` → 422, pesan menyebut "batas server").
- `UploadedFile::fake()` selalu `error=0`, jadi tidak pernah menguji jalur tolakan PHP — test memakai `new UploadedFile($path, $name, $mime, UPLOAD_ERR_INI_SIZE, true)`.
- **Temuan penting (production):** PHP distro default `upload_max_filesize=2M`, sedangkan fitur menjanjikan 5 MB. Diverifikasi empiris via probe upload: 1,5 MB lolos, 2,5 MB → `UPLOAD_ERR_INI_SIZE`. Diperbaiki di akar: `deploy/watchdog.sh` sekarang menjalankan `php -S ... -d upload_max_filesize=6M -d post_max_size=8M` (flag **setelah** `-S 127.0.0.1:8000` agar pola `pgrep`/`pkill`/guard `^php -S 127\.0\.0\.1:8000` tetap cocok; bisa di-override lewat env `UPLOAD_MAX_FILESIZE`/`POST_MAX_SIZE`).

**Dependencies:** Task 1, Task 2, Task 3

**Files likely touched:**

- `api/routes/api.php`
- `api/app/Http/Controllers/AiController.php`
- `api/app/Http/Requests/ParseCvRequest.php`
- `api/tests/Feature/AiParseCvTest.php`

**Estimated scope:** Medium (4 files)

### Checkpoint A: setelah Task 1–4

- [x] `./vendor/bin/phpunit` lulus seluruh suite (79 test, 206 assertion) — jangan `php artisan test`.
- [x] `curl` tanpa auth ke `/api/v1/ai/parse-cv` membalas `401`, bukan `500`/redirect.
- [x] Review dengan user sebelum menyentuh frontend. (disetujui user)

---

## Task 5: Klien API frontend (multipart)

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Helper `req` di `web/src/api/cv.ts` selalu menulis `Content-Type: application/json`, itu merusak upload multipart karena boundary tidak terisi. Tambah jalur terpisah untuk `FormData`.

**Acceptance criteria:**

- Fungsi `parseCv(file: File): Promise<{ data: CvData; warnings: string[] }>` di `cvApi`.
- Memakai `FormData`, `credentials: "include"`, header `X-XSRF-TOKEN`, dan **tidak** men-set `Content-Type` manual (biar browser mengisi boundary).
- `csrf()` dipanggil dulu (pola sama dengan `create`/`update`).
- Error `422`/`502`/`429` dilempar sebagai `Error` dengan `status` dan `errors`, sama seperti `req`.

**Verifikasi:**

- [x] `pnpm type-check` 0 error.
- [ ] Uji manual: panggilan mengembalikan objek `data` (dicek di T7).

**Catatan implementasi:**

- Logika terjemahan respons diekstrak ke `parseResponse<T>(res)` yang dipakai bersama `req` (JSON) dan `reqForm` (multipart). Tanpa ini, bentuk `Error` (dengan `status`/`errors`) harus diduplikasi di dua tempat dan bisa menyimpang.
- `reqForm` memakai `method: "POST"` dan **tidak** menyetel `Content-Type`; hanya `Accept` + `X-XSRF-TOKEN`. Ini yang membuat browser mengisi `boundary` sendiri.
- `parseCv` mengembalikan `{ data, warnings }` persis seperti kontrak T4; `data` bertipe `CvData` (form mengisi via `normalizeCvData` di T7).

**Dependencies:** Task 4

**Files likely touched:**

- `web/src/api/cv.ts`

**Estimated scope:** Small (1 file)

---

## Task 6: ImportCvPanel.vue (komponen upload)

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Satu komponen upload yang ditaruh di atas `CvForm`. Menangani pilih file, validasi klien, kirim, dan status.

**Acceptance criteria:**

- Validasi klien sebelum kirim: ekstensi `.pdf`/`.docx`, ukuran ≤ 5 MB. Pesan Indonesia.
- Status jelas: idle, loading (spinner + teks "Membaca CV..."), error, sukses.
- `aria-busy` saat loading, tombol `:disabled` saat loading (R-27).
- Ada label dan hint format yang didukung. Bukan input file polos tanpa penjelasan.
- Emit `imported` dengan `{ data, warnings }`, atau `error` dengan pesan.
- Gaya mengikuti `DESIGN.md` (palet, border, shadow), tidak mengarang gaya baru.

**Verifikasi:**

- [x] `pnpm type-check` 0 error.
- [x] Verifikasi browser: pilih file salah → error inline; file benar → spinner lalu sukses.

**Catatan implementasi:**

- Validasi klien berurutan: **ekstensi dulu, baru ukuran**, keduanya sebelum network. `.txt` → "Format tidak didukung. Upload berkas PDF atau DOCX."; 6 MB → "Ukuran berkas maksimal 5 MB.". Keduanya `role="alert"`.
- Input file asli disembunyikan dengan `sr-only` (1×1, `clip: inset(50%)`), tombol "Pilih berkas" yang memicunya. Ini tetap aksesibel (label + `aria-labelledby`).
- Pemetaan error: 401 → sesi berakhir; 422 → pesan server/berkas tak terbaca; 429 → terlalu banyak percobaan; 502 → layanan AI bermasalah; 503 → fitur AI belum dikonfigurasi; default → pesan generik. Konsisten dengan bentuk `Error` dari `parseResponse` (T5).
- **Gotcha Playwright:** ada dua `input[type=file]` di halaman (panel impor + foto profil). Wajib scope: `page.locator('section[aria-labelledby="import-cv-heading"]').locator('input[type=file]')`, jika tidak kena strict-mode violation.
- **Verifikasi state sukses dilakukan dua kali:** (1) lewat mock `page.route('**/api/v1/ai/parse-cv')` saat gateway belum berfungsi, lalu (2) **end-to-end nyata** setelah akar 502 diperbaiki di `AiService` (lihat T6b): upload `cv-valid.pdf` → HTTP 200 → panel menampilkan "CV berhasil dibaca." di mode terang dan gelap.
- State loading terukur: `aria-busy="true"`, teks "Membaca CV...", tombol "Pilih berkas" `disabled`, 1 elemen spinner.

**Dependencies:** Task 5

**Files likely touched:**

- `web/src/components/cv/ImportCvPanel.vue`
- `web/src/views/CvFormView.vue` (tempat menaruh komponen)

**Estimated scope:** Medium (2 files)

---

## Task 6b: Perbaikan akar — gateway membuang pesan `system`

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Import gagal 502 saat memakai model deepseek/glm. Setelah T6, jalur sukses hanya terbukti lewat mock; sebabnya adalah perilaku gateway, bukan kode.

**Temuan (dibuktikan lewat tes "kata rahasia"):**

Gateway AI lokal (9Router, `localhost:20128`) **membuang pesan `system`** untuk model deepseek/glm. Tes: kata rahasia ditaruh di pesan `system`, ditanya dari pesan `user`.

| Model                 | `system` dihormati? |
| --------------------- | ------------------- |
| `deepseek-v4.1-flash` | ❌ dibuang          |
| `glm-5.3-flash`       | ❌ dibuang          |
| `cx/gpt-5.5`          | ✅ dihormati        |

Karena `AiService` menaruh instruksi "balas HANYA JSON" di pesan `system`, model deepseek/glm hanya melihat teks CV mentah lalu membalas prosa → `ekstrakJson()` melempar `RuntimeException: Respons AI tidak berisi JSON.` → `AiController` memetakan ke 502.

**Perbaikan:** Gabungkan prompt sistem + data jadi **satu pesan `user`** lewat helper `pesanGabung()` di `AiService`. Diterapkan di `structureCvData()` dan `generateSummary()` (keduanya memakai gateway yang sama). Parameter alternatif (`response_format`, `enable_thinking`, `thinking:{type:disabled}`, `chat_template_kwargs`) **tidak** dipakai karena diabaikan gateway.

**Verifikasi:**

- [x] Test merah dulu (TDD): `test_instruksi_dikirim_di_pesan_user_bukan_system` gagal (2 pesan) sebelum perbaikan.
- [x] `./vendor/bin/phpunit --filter AiServiceStructureTest` → 14/14, 34 assertion.
- [x] `./vendor/bin/phpunit --filter AiServiceSummaryTest` → 2/2, 7 assertion (baru).
- [x] Suite penuh → **82 test, 218 assertion, semua lulus** (sebelumnya 80/211).
- [x] End-to-end nyata di browser: upload `cv-valid.pdf` dengan `AI_MODEL=NgodingYok` → HTTP 200 `{data, warnings}` → panel "CV berhasil dibaca." (terang + gelap).
- [x] `get_errors` 0 error di 3 berkas yang disentuh.

**Files likely touched:**

- `api/app/Services/AiService.php`
- `api/tests/Unit/AiServiceStructureTest.php`
- `api/tests/Unit/AiServiceSummaryTest.php` (baru)

**Estimated scope:** Small (3 files)

---

## Task 7: Isi form dari hasil import

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Sambungkan hasil parse ke state form yang sudah ada. `CvFormView` yang memegang `data`, jadi di situ normalisasi dilakukan.

**Acceptance criteria:**

- Hasil `data` dilewatkan `normalizeCvData()` sebelum masuk `data.value` (data lama/AI bisa saja tidak lengkap).
- Form mengisi seluruh step (pribadi, pengalaman, pendidikan, keahlian, dst.), bukan hanya ringkasan.
- Bila `warnings` tidak kosong, tampilkan toast yang menyebut field yang perlu dilengkapi user.
- Tidak ada auto-save. User tetap harus menekan Simpan.
- Bila user sudah mengisi form, konfirmasi dulu sebelum menimpa (jangan diam-diam menghapus ketikan).

**Implementasi:**

- `onImported(payload)` di `CvFormView`: cek `formTerisi()` → `confirm()` bila ada ketikan → `normalizeCvData()` → timpa `data.value`.
- Foto profil dan pilihan font **dipertahankan** saat impor: AI tidak membacanya dari berkas, jadi menimpanya = menghapus pilihan user.
- Judul CV selalu diganti nama hasil parse (kalau ada): form kosong → terisi pertama; form terisi + user setuju timpa → judul lama tidak lagi cocok dengan isi baru.
- Toast sukses normal 2,6 dtk; toast `warnings` 6 dtk (merah) menyebut field: `CV berhasil dibaca. Lengkapi: Pengalaman kerja, Pendidikan, Keahlian.`

**Verifikasi:**

- [x] Verifikasi browser end-to-end: upload `cv-valid.pdf` → HTTP 200 → judul + seluruh step terisi → preview ikut berubah (step Pribadi: nama/email/telepon/alamat terisi benar).
- [x] Field kosong dilaporkan di toast: upload `cv-warn.pdf` (tanpa pengalaman/pendidikan/keahlian) → `CV berhasil dibaca. Lengkapi: Pengalaman kerja, Pendidikan, Keahlian.`
- [x] Konfirmasi: impor ke-2 saat form terisi → dialog muncul; **Batal** → data lama utuh + toast `Impor dibatalkan.`; **Lanjutkan** → data + judul tertimpa.
- [x] Alur penuh: impor → Simpan CV → muncul di dashboard (`/dashboard`), Edit membuka kembali data tersimpan, screenshot terang + gelap.
- [x] `pnpm type-check` 0 error; `pnpm build-only` sukses (EXIT=0, 2506 modul).
- [x] `get_errors` 0 error di `CvFormView.vue`.

**Dependencies:** Task 6

**Files likely touched:**

- `web/src/views/CvFormView.vue`

**Estimated scope:** Small (1 file)

---

## Task 7b: Perbaiki validasi hasil impor (field merah + navigasi)

> Status: ✅ **Selesai** (2026-10-06)

**Deskripsi:** Ditemukan saat user menguji Task 7. Setelah impor, ada isian yang melanggar aturan tapi UI hanya menampilkan pesan generik "Ada isian yang perlu diperbaiki" tanpa penanda merah dan tanpa pindah ke field-nya.

Dua bug kelas sama:

1. **Keahlian** — `CvForm` tidak meneruskan `:errors` ke `SkillsStep`, padahal step itu sudah siap menerimanya. AI sering mengembalikan grup skill dengan `label: ""` tapi `items` terisi, sehingga validasi `skills.N.label` dihitung tapi tidak pernah dirender. Akibatnya stepper menandai error tetapi field-nya bersih.
2. **Ringkasan > 600 karakter** — `SummaryStep` tidak punya prop `errors`, `CvForm` tidak mengirimnya, dan `cv-validation.ts` tidak punya aturan batas panjang. Import (`AiService::normalisasiCvData`) juga tidak memotong ringkasan, jadi AI bisa menyuntik > 600 karakter. Server lalu membalas `422` dengan pesan **Inggris** dan `applyServerErrors` tidak bisa pindah step (path `summary` tidak ada di daftar kandidat).

**Acceptance criteria:**

- Field yang melanggar (termasuk hasil impor) menampilkan border merah + pesan, dan stepper menandai step pemiliknya.
- Submit yang gagal memindahkan user ke step error pertama dan memfokuskan field-nya.
- Pesan pelanggaran batas panjang menjelaskan kelebihan karakter, dalam bahasa Indonesia.
- Hasil impor AI selalu lolos validasi simpan (ringkasan dipotong, grup skill tanpa label diberi label).

**Implementasi:**

- `web/src/components/cv/CvForm.vue`: kirim `:errors="fieldErrors"` ke `SkillsStep`, `SummaryStep`, `OtherStep`; bungkus step 2/6/9 dengan `div.cv-step-active` (step lain sudah punya) supaya `focusStepHeading()` menemukan heading.
- `web/src/components/cv/steps/SummaryStep.vue`: prop `errors` + `:error="err('summary')"` pada `FormTextarea`; counter berubah merah + menyebut batas saat `> 600`.
- `web/src/components/cv/steps/OtherStep.vue`: prop `errors` + `:error="err('languages')"` pada input Bahasa.
- `web/src/lib/cv-validation.ts`: `MAX_LENGTHS` (summary 600, languages 200) ikut `collectInvalid` → error tampil sebelum 422 dan navigasi `applyServerErrors` bekerja. Pesan: `Ringkasan maksimal 600 karakter — kelebihan 320 karakter.`
- `api/app/Http/Requests/StoreCvRequest.php`: pesan Indonesia `data.summary.max` + `data.languages.max` (jaring pengaman bila 422 tetap lolos).
- `api/app/Services/AiService.php`: `normalisasiCvData()` memotong ringkasan ke 600 (konsisten `generateSummary`); grup skill berisi `items` tapi `label` kosong diberi label `Keahlian`; prompt ekstraksi ditambah aturan "summary maks 600 karakter" + "setiap grup skills wajib punya label".

**Verifikasi:**

- [x] Bug 2 (browser, mock impor ringkasan 920 char): submit → alert muncul, stepper auto-pindah ke `! Ringkasan`, 2 border merah, pesan `Ringkasan maksimal 600 karakter — kelebihan 320 karakter.`, counter merah, fokus ke heading Ringkasan.
- [x] Bug 1 (browser, mock grup skill label kosong): `#skill-2-label` `aria-invalid=true`, border merah, pesan `Nama grup keahlian #1 wajib diisi.`; stepper `! Keahlian`.
- [x] Perbaiki field → error auto-hilang (ariaInvalid 0, alert hilang, counter normal); simpan → sukses masuk dashboard (Total CV 2).
- [x] Mode gelap: border/pesan/counter tetap merah (`oklch(0.808 0.114 19.571)` = red-300 dark), tidak tertimpa `.dark .cv-form p`.
- [x] Test backend baru: `AiServiceStructureTest` (potong summary 600, fallback label, grup kosong tetap kosong) + `CvMaxLengthValidationMessagesTest` (pesan ID + batas 600 lolos).
- [x] `./vendor/bin/phpunit` seluruh suite lulus (88 test, 225 assertion) — naik dari 82/218, tanpa regresi.
- [x] `pnpm type-check` 0 error; `pnpm build-only` sukses (EXIT=0).

**Dependencies:** Task 7

**Files touched:**

- `web/src/components/cv/CvForm.vue`
- `web/src/components/cv/steps/SummaryStep.vue`
- `web/src/components/cv/steps/OtherStep.vue`
- `web/src/lib/cv-validation.ts`
- `api/app/Http/Requests/StoreCvRequest.php`
- `api/app/Services/AiService.php`
- `api/tests/Unit/AiServiceStructureTest.php`
- `api/tests/Feature/CvMaxLengthValidationMessagesTest.php` (baru)

**Estimated scope:** Medium (8 files)

### Checkpoint B: setelah Task 5–7

- [x] `pnpm type-check` 0 error, `pnpm build-only` sukses.
- [x] Alur penuh jalan di browser: upload → form terisi → Simpan → muncul di dashboard.
- [x] Review dengan user sebelum update dokumen. (disetujui user, 2026-10-07)

---

## Task 8: Update dokumen

> Status: ✅ **Selesai** (2026-10-07)

**Deskripsi:** Samakan dokumen dengan kenyataan. Backlog §7a sudah menyebut dampak ini.

**Acceptance criteria:**

- `docs/API_SPEC.md`: tambah `POST /ai/parse-cv` (body, response, semua kode error).
- `docs/PRD.md`: F2 dan F5 menyebut jalur import.
- `docs/ARCHITECTURE.md`: ADR-5 menyebut file handling + `pdfparser`, atau ADR baru bila DOCX butuh keputusan tersendiri.
- `docs/DATA_MODEL.md`: catatan bahwa output import dipetakan ke skema existing (tidak ada perubahan skema).
- `docs/ROADMAP.md` dan `docs/phases/phase-7-post-mvp.md`: tautkan ke dokumen ini dan tandai statusnya.

**Implementasi:**

- `API_SPEC.md` §AI: entri `POST /ai/parse-cv` lengkap — tabel field request, contoh respons `{ data, warnings }`, tabel kode error (`401/422/429/502/503`), batasan v1, dan rantai implementasi (`DocumentTextExtractor` → `AiService::structureCvData`).
- `PRD.md` F2: menyebut impor CV PDF/DOCX (fase-7 7a ✅ 2026-10-07). F5: menautkan gateway yang sama untuk impor.
- `ARCHITECTURE.md` ADR-5: butir "File handling + import CV (2026-10-07)" — pdfparser + ZipArchive, berkas tidak disimpan, normalisasi output, limiter `ai-parse`, catatan `pesanGabung()`.
- `DATA_MODEL.md`: blockquote di bawah tabel `cvs` — import tidak mengubah skema.
- `ROADMAP.md` Fase 7: item impor jadi `[x]` + tanggal. `phase-7-post-mvp.md` §7a: judul ✅, kontrak final, dampak docs ✅, catatan eksekusi.

**Verifikasi:**

- [x] Semua tautan antar dokumen tidak putus (cek relatif `phase-7a-import-cv.md` dari `API_SPEC.md`/`ROADMAP.md`/`phase-7-post-mvp.md`).
- [x] `git status` hanya memuat berkas yang disengaja (semua bagian Fase 7a; tidak ada artefak build/`.env`).

**Dependencies:** Task 4, Task 7

**Files touched:**

- `docs/API_SPEC.md`, `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DATA_MODEL.md`, `docs/ROADMAP.md`, `docs/phases/phase-7-post-mvp.md`

**Estimated scope:** Medium (6 files)

---

## Risiko

| Risiko                                   | Dampak                    | Mitigasi                                                         |
| ---------------------------------------- | ------------------------- | ---------------------------------------------------------------- |
| AI mengarang isi CV                      | Data palsu masuk form     | Prompt hanya-fakta + user review + tidak auto-save               |
| `smalot/pdfparser` gagal di PDF tertentu | `422` padahal file sah    | Tangkap exception, pesan jelas, sediakan jalur DOCX              |
| Teks panjang melebihi token              | `502` atau JSON terpotong | Potong input (mis. 15000 char) sebelum kirim ke AI               |
| Parsing JSON AI tidak stabil             | Import sering gagal       | Ekstraksi tahan banting + test dengan respons bervariasi         |
| Waktu proses lama (ekstrak + AI)         | Timeout                   | `set_time_limit` seperti `generateSummary`, beri tahu user di UI |

## Definisi Selesai

- [ ] Upload PDF/DOCX text-based ≤ 5 MB → form terisi, user bisa edit dan simpan.
- [ ] Scan/gambar → `422` dengan pesan "upload PDF text-based".
- [ ] File > 5 MB atau > 5 halaman → `422`.
- [ ] Request ke-4 dalam 1 menit → `429`.
- [ ] AI down → `502`, tanpa file tersisa di disk.
- [ ] Tidak ada auto-save.
- [ ] `./vendor/bin/phpunit` lulus, `pnpm type-check` 0 error, `pnpm build-only` sukses.
- [ ] Dokumen diperbarui.

## Referensi

- Backlog asal: [phase-7-post-mvp.md §7a](phase-7-post-mvp.md)
- Kontrak AI: [API Spec — AI](../API_SPEC.md#ai)
- Skema data: [DATA_MODEL.md](../DATA_MODEL.md)
- Pola endpoint AI: `api/app/Http/Controllers/AiController.php`, `api/app/Services/AiService.php`
- Rate limiter: `api/app/Providers/AppServiceProvider.php`
- Upload multipart: [Laravel docs — File validation](https://github.com/laravel/docs/blob/13.x/validation.md) (via Context7)

← [Fase 7 — Post-MVP](phase-7-post-mvp.md) · Kembali ke [Roadmap](../ROADMAP.md)
