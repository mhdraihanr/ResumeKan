# deploy/

Perkakas deploy untuk container Railway (tanpa systemd). Penjelasan langkah demi langkah ada di
[../docs/DEPLOY_VPS_PEMULA.md](../docs/DEPLOY_VPS_PEMULA.md); folder ini isinya berkas yang dipakai skrip.

| Berkas                    | Fungsi                                                                     |
| ------------------------- | -------------------------------------------------------------------------- |
| `deploy.sh`               | Bangun container dari nol: kode, `.env`, Caddyfile, build, jalankan proses |
| `update.sh`               | Pasang kode terbaru ke container yang sedang hidup                         |
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
- **Tidak** mengubah `api/bootstrap/app.php` dan `api/app/Providers/AppServiceProvider.php`. Kalau `trustProxies` dan
  `forceScheme` belum ada di sana, isi dulu dengan mengikuti Langkah 8 panduan, lalu commit supaya tidak perlu mengulang.
- **Tidak** memasang perkakas sistem (PHP, Node, Caddy, Chromium). Itu Langkah 4 dan 5 panduan, dan hanya perlu saat
  container benar-benar baru.
