# Rencana Upgrade: PDF Single-Source (Opsi A) + Template Layout Baru

> **Status:** ✅ terealisasi dan diperbaiki 2026-08-31. Preview dan PDF memakai markup Vue yang sama. Setelah signed print URL menyebabkan deadlock di server Laravel development satu-proses, renderer diubah ke HTML langsung. Template tetap 1 file per template (`CvModern.vue`/`CvClassic.vue`/`CvNeon.vue`, header include masing-masing). Dokumen ini menyimpan keputusan akhir dan konteks historisnya.
> **Pemicu awal:** variasi struktur template, termasuk versi Neon lama yang mixed-column, membuat duplikasi Blade tidak lagi murah. Neon saat ini memakai dokumen satu kolom dengan divider mint dan foto persegi opsional.

## 1. Latar Belakang

Arsitektur lama menggunakan preview Vue dan PDF Blade sebagai dua sumber markup. Setiap perubahan layout perlu disinkronkan manual.

Duplikasi itu murah selama template hanya berbeda token, misalnya font, warna, dan alignment. Saat struktur berbeda, misalnya layout dua kolom, foto profil, atau header berwarna, Blade harus meniru seluruh tree HTML. Single-source menghilangkan risiko tersebut.

## 2. Keputusan Implementasi Akhir

### 2.1 Single-source: HTML print yang sama diberikan langsung ke Browsershot

`CvController@pdf` memeriksa kepemilikan CV lalu memanggil `resolvePrintHtml($data, $template)`. Method itu mengambil `print.html` dan menyisipkan `window.__CV_DATA__` serta `window.__CV_TEMPLATE__`. HTML hasilnya diteruskan langsung ke `PdfService::render(string $html)`.

```text
GET /api/v1/cvs/{cv}/pdf (cookie Sanctum + owner check)
  → CvController@pdf
  → resolvePrintHtml(data, template)
  → Browsershot::html($html)
  → PDF attachment
```

`Browsershot::url()` tidak dipakai pada jalur PDF. Saat dipakai, Chromium meminta `/print` kembali ke proses Laravel yang sedang menangani `/pdf`; server development satu-proses tidak bisa melayani request kedua dan berhenti setelah batas eksekusi 30 detik.

### 2.2 Data tetap di-embed, tidak ada fetch API dari print app

```html
<script>
  window.__CV_DATA__ = {{ Js::from($cv->data) }};
  window.__CV_TEMPLATE__ = {{ Js::from($cv->template) }};
</script>
```

`print-main.ts` membaca dua nilai tersebut dan mount `CvPreview.vue` tanpa router, Pinia, atau navbar. Jadi isi PDF selalu dirender dari komponen template Vue yang juga dipakai preview.

### 2.3 Shell print dan module Vite

`Browsershot::html()` menyimpan HTML sebagai halaman `file://` sementara. ES module dari Vite atau aset build berada di `FRONTEND_URL`, sehingga Chromium memerlukan argumen berikut agar module lintas-origin dari shell lokal dapat dimuat:

```php
$shot->addChromiumArguments([
    'disable-web-security',
    'allow-file-access-from-files',
]);
```

Kedua argumen hanya mendukung render shell aplikasi sendiri. `FRONTEND_URL` harus menunjuk origin frontend yang tepercaya dan dapat dijangkau proses Chromium.

### 2.4 Route print bertanda tangan hanya untuk inspeksi internal

```php
Route::get('/cvs/{cv}/print', [CvController::class, 'print'])
    ->middleware('signed')
    ->name('cvs.print');
```

Route `GET /api/v1/cvs/{cv}/print?expires=...&signature=...` tetap ada untuk memeriksa shell HTML, bukan untuk dipanggil frontend atau `PdfService`. Tidak ada cookie Sanctum yang diperlukan pada route ini karena data CV dibuka melalui signature yang diterbitkan server.

### 2.5 PdfService

```php
class PdfService
{
    public function render(string $html): string
    {
        return Browsershot::html($html)
            ->format('A4')
            ->margins(14, 16, 14, 16)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->addChromiumArguments([
                'disable-web-security',
                'allow-file-access-from-files',
            ])
            ->pdf();
    }
}
```

Di Windows, service memakai Microsoft Edge Chromium apabila path lokalnya ada. Jika tidak, Browsershot memakai browser yang disediakan Puppeteer.

### 2.6 Print CSS

`CvPreview.vue` sudah mendefinisikan CSS print A4 dan link tanpa underline. Browsershot mengatur ukuran serta margin A4, lalu menunggu module Vue selesai merender sebelum membuat PDF.

## 3. Struktur Target

```
web/
├── print.html                  # entry print tanpa app shell
├── src/
│   ├── print-main.ts           # mount CvPreview dari window.__CV_DATA__/__CV_TEMPLATE__
│   └── components/cv/
│       ├── CvPreview.vue       # router: pilih CvModern/CvClassic/CvNeon
│       ├── templates/
│       │   ├── CvModern.vue    # header + body single-column (navy)
│       │   ├── CvClassic.vue   # header center + serif + split otherMode
│       │   └── CvNeon.vue      # header kiri, satu kolom, divider mint, foto opsional
│       └── sections/           # PreviewSection, EntryRow, BulletList (shared)

api/
├── app/
│   ├── Http/Controllers/CvController.php   # membangun shell untuk pdf/print
│   └── Services/PdfService.php             # ::html() + Chromium options
├── routes/api.php                           # route cvs.print signed, inspeksi internal
└── (config via app.frontend_url, bukan config/cv.php)

DIHAPUS:
└── resources/views/pdf/cv.blade.php
```

## 4. Fase Eksekusi

### Fase A: Entry print SPA

- Buat `web/print.html` dan `web/src/print-main.ts` untuk mount `CvPreview`.
- Tambahkan `print.html` sebagai input Vite kedua.
- Verifikasi shell bisa render dari data inline.

### Fase B: Controller print shell

- Tambahkan `resolvePrintHtml()` untuk menyisipkan data dan template.
- Tambahkan route signed `/print` untuk inspeksi shell tanpa session browser.
- Dev memakai module Vite. Produksi memakai `web/dist/print.html` dengan URL aset yang mengarah ke `FRONTEND_URL`.

### Fase C: Render HTML langsung

- Ubah `PdfService::render()` menjadi `Browsershot::html($html)`.
- Tambahkan argumen Chromium yang diperlukan shell `file://` untuk memuat module Vite atau aset build.
- Verifikasi endpoint PDF tidak lagi timeout dan hasilnya tidak blank.

### Fase D: Cleanup

- Hapus `api/resources/views/pdf/cv.blade.php`.
- Update ADR-4 dan kontrak API.

## 5. Risiko dan Mitigasi

| Risiko                                               | Mitigasi                                                                            |
| ---------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Request PDF melakukan request balik ke server API    | Render HTML langsung dengan `Browsershot::html()`, bukan `Browsershot::url()`       |
| PDF blank karena module Vite diblokir dari `file://` | Tambahkan `disable-web-security` dan `allow-file-access-from-files` pada Chromium   |
| `FRONTEND_URL` tidak dapat diakses Chromium          | Pastikan origin frontend lokal atau aset production dapat dijangkau dari proses API |
| Bundle print berbeda dari app                        | Print entry mengimpor CSS yang sama dan mount `CvPreview` yang sama                 |
| `waitUntilNetworkIdle` hang                          | Data CV sudah di-embed, jadi print app tidak memanggil API                          |
| Headless Chrome berbeda dari browser                 | Verifikasi PDF nyata lewat endpoint dan bandingkan dengan preview                   |

## 6. Template Layout Baru Setelah Single-Source

Nambah template struktur baru menjadi kerja frontend:

1. Tambahkan token di `cv-templates.ts`.
2. Buat `templates/CvNamaBaru.vue` dengan header dan body self-contained.
3. Tambahkan satu cabang pada `comp` di `CvPreview.vue`.

PDF otomatis ikut karena shell print mount `CvPreview` yang sama. Ketiga template memakai body satu kolom. Neon menambahkan divider mint `#6ee7b7`, grid kontak responsif, dan foto persegi dari `personal.photo`; tidak ada QR atau border luar.

`personal.photo` kini di-upload ke Cloudinary (signed upload via `POST /upload-signature`) dan hanya dipakai Neon. Validasi MIME/ukuran ada di klien (`uploadPhoto`, maks 2 MB); kompresi server-side tetap fitur terpisah.

## 7. Riwayat Implementasi

1. `feat(web): entry print.html untuk render PDF single-source`
2. `feat(api): route cvs.print signed + serve print shell`
3. `refactor(pdf): render HTML print langsung dengan Browsershot`
4. `chore: hapus cv.blade.php + update ADR-4 single-source`
5. `refactor(cv): 1 template = 1 file (CvModern/CvClassic/CvNeon, header include masing-masing)`

## 8. Referensi

- Spatie Browsershot: https://spatie.be/docs/browsershot
- Spatie Browsershot discussion tentang timeout Vite: https://github.com/spatie/laravel-pdf/discussions/217
- André Arko tentang perbedaan headless Chrome saat print: https://andre.arko.net/2025/05/25/chrome-headless-print-to-pdf/
