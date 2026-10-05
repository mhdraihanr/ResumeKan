# deploy/

Perkakas deploy untuk container Railway (tanpa systemd). Panduan singkat ada di
[../docs/DEPLOY_VPS_PEMULA.md](../docs/DEPLOY_VPS_PEMULA.md), sedangkan penjelasan langkah demi langkah beserta cara
memperbaiki kegagalan ada di [../docs/DEPLOY_VPS_TEKNIS.md](../docs/DEPLOY_VPS_TEKNIS.md); folder ini isinya berkas yang
dipakai skrip.

| Berkas                    | Fungsi                                                                     |
| ------------------------- | -------------------------------------------------------------------------- |
| `deploy.sh`               | Bangun container dari nol: kode, `.env`, Caddyfile, build, jalankan proses |
| `update.sh`               | Pasang kode terbaru ke container yang sedang hidup                         |
| `install-deps.sh`         | Pasang perkakas sistem (PHP 8.4, Node 22, Caddy, Chromium); idempotent     |
| `lib-dist.sh`             | Helper unduh hasil build frontend dari GitHub Actions (dipakai dua skrip)  |
| `Caddyfile`               | Konfigurasi Caddy satu host (:8080, SPA + reverse proxy `/api/*`)          |
| `watchdog.sh`             | Penjaga `php -S`, pengganti systemd                                        |
| `service-run.sh`          | Entry point runit, disalin ke `/etc/service/resumekan-api/run`             |
| `.env.production.example` | Contoh pengaturan produksi, disalin jadi `.env.production` lalu diisi      |

## Pakai

Di laptop, sekali saja:

```bash
bash deploy/deploy.sh --template
# isi nilai ISI_ di deploy/.env.production
```

Kirim ke container (kecuali kode sudah ada di sana):

```bash
scp -P 53327 deploy/.env.production root@zephyr.proxy.rlwy.net:/srv/ResumeKan/deploy/.env.production
```

Di container:

```bash
bash /srv/ResumeKan/deploy/deploy.sh     # container baru / setelah redeploy
bash /srv/ResumeKan/deploy/update.sh     # update kode rutin
```

## Build frontend: dari CI, bukan dari container

Container Railway free hanya punya 0.5 CPU / 512 MB. `vite build` di sana tampak hang di `transforming (xxxx)`
selama puluhan menit. Karena itu `.github/workflows/build-web.yml` membangun `web/` di runner GitHub (2 core / 7 GB),
lalu menerbitkan `web-dist.tar.gz` sebagai release asset di tag tetap `dist-latest`. Langkah "Frontend" di kedua skrip
hanya mengunduh URL itu lewat `lib-dist.sh` dan mengekstraknya ke `web/dist`.

Alur kerja jadi: **push ke `main` -> tunggu workflow "Build Web" hijau -> jalankan `update.sh` di container.** Kalau
workflow belum selesai atau URL tidak bisa diunduh, skrip otomatis jatuh ke `pnpm install && pnpm type-check &&
pnpm build:ci` (lambat, tapi tetap jalan).

URL yang dipakai (bisa ditimpa lewat `DIST_URL`):

```
https://github.com/mhdraihanr/ResumeKan/releases/download/dist-latest/web-dist.tar.gz
```

`deploy/.env.production` tidak masuk git, jadi salinan lokalnya adalah satu-satunya contoh yang bisa dibaca ulang.
Simpan juga di manajer sandi.

## Yang tidak dilakukan skrip ini

- **Tidak** menyentuh database selain menjalankan migrasi yang pending.
- **Tidak** memakai `migrate:fresh`. Database Neon tetap utuh antar redeploy.
- **Tidak** mengubah `api/bootstrap/app.php` dan `api/app/Providers/AppServiceProvider.php`. Ketiga suntingan produksi
  (termasuk `api/app/Services/PdfService.php`) sudah ada di repo sejak commit `31be3af`, jadi tidak ada langkah manual
  yang tertinggal.
- **Tidak** memasang swap, jam WIB, atau setelan memori kernel. Itu langkah manual di panduan (Langkah 4 di
  [../docs/DEPLOY_VPS_PEMULA.md](../docs/DEPLOY_VPS_PEMULA.md)), dan hanya perlu saat container benar-benar baru.
- **Tidak** memasang perkakas sistem secara manual: `deploy.sh` memanggil `install-deps.sh` otomatis kalau ada yang
  hilang.
