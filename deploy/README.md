# deploy/

Perkakas deploy untuk container Railway (tanpa systemd). Penjelasan langkah demi langkah ada di
[../docs/DEPLOY_VPS_PEMULA.md](../docs/DEPLOY_VPS_PEMULA.md); folder ini isinya berkas yang dipakai skrip.

| Berkas                    | Fungsi                                                                     |
| ------------------------- | -------------------------------------------------------------------------- |
| `deploy.sh`               | Bangun container dari nol: kode, `.env`, Caddyfile, build, jalankan proses |
| `update.sh`               | Pasang kode terbaru ke container yang sedang hidup                         |
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
scp -P 53337 deploy/.env.production root@zephyr.proxy.rlwy.net:/srv/ResumeKan/deploy/.env.production
```

Di container:

```bash
bash /srv/ResumeKan/deploy/deploy.sh     # container baru / setelah redeploy
bash /srv/ResumeKan/deploy/update.sh     # update kode rutin
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
