# deploy/

Dua jalur deploy. **Pilih satu** — jangan campur.

| Jalur                 | Kapan dipakai                                   | Perintah                                                           |
| --------------------- | ----------------------------------------------- | ------------------------------------------------------------------ |
| **VPS (rekomendasi)** | Debian 12/13, Ubuntu 22.04/24.04 dengan systemd | `sudo bash deploy/bootstrap.sh` lalu `sudo bash deploy/release.sh` |
| Legacy (Railway)      | Container tanpa systemd                         | `deploy.sh` / `update.sh`                                          |

Panduan literatur lengkap: [../docs/DEPLOY_DEBIAN12_CLOUDFLARE_TUNNEL.md](../docs/DEPLOY_DEBIAN12_CLOUDFLARE_TUNNEL.md) dan
[../docs/DEPLOY_VPS_PEMULA.md](../docs/DEPLOY_VPS_PEMULA.md) / [../docs/DEPLOY_VPS_TEKNIS.md](../docs/DEPLOY_VPS_TEKNIS.md).

## Jalur VPS (systemd)

```bash
# di VPS, sebagai root
sudo bash deploy/bootstrap.sh --check      # audit saja, tidak mengubah apa pun
sudo bash deploy/bootstrap.sh              # setup penuh, idempoten

# setelah .env diisi dan migrate dijalankan
sudo bash deploy/release.sh --if-changed   # update rutin; no-op kalau sudah versi terbaru
```

Bootstrap menjalankan 12 langkah: paket dasar → PHP 8.4 (Sury untuk Debian, PPA
ondrej/php untuk Ubuntu) → Composer → Node+pnpm → Chromium/Chrome → Caddy →
cloudflared → user `resumekan` + clone → `composer install` → template `.env` →
artifact frontend CI → unit systemd + Caddyfile + (opsional) timer update.

Release menggantikan `update.sh` untuk VPS dan:

- **tidak** memakai `git reset --hard`; suntingan lokal disimpan sebagai patch di
  `/root/` dan merge memakai `git merge --ff-only` (kalau konflik, berhenti);
- menjalankan `npm ci` di `api/` supaya Puppeteer punya modul;
- hanya menjalankan `migrate` kalau ada pending;
- health check 3 lapis: backend `:8000/up`, Caddy `:8080/up`, opsional `PUBLIC_URL`;
- upgrade paket PHP sebelum restart (patch keamanan berlaku ke proses baru).

### Auto-update (pull-based)

VPS di balik NAT tidak bisa dijangkau GitHub Actions, jadi arahnya ditarik dari dalam:

```bash
sudo systemctl enable --now resumekan-update.timer
systemctl list-timers resumekan-update.timer
journalctl -u resumekan-update -n 50 --no-pager
```

Timer berjalan tiap 15 menit dan memanggil `release.sh --if-changed`. Kalau SHA
`origin/main` sama dengan lokal, timer langsung keluar tanpa menyentuh apa pun.
Mengaktifkan sekaligus dari bootstrap: `ENABLE_AUTO_UPDATE=1 sudo bash deploy/bootstrap.sh`.

**Penting:** auto-update hanya aman kalau workflow **API Check** dan **Build Web**
hijau untuk commit yang sama (keduanya otomatis pada push ke `main`). Jangan
pernah meng-commit secret ke `main` — timer akan memasangnya tanpa jeda.

## Jalur Legacy (Railway)

| Berkas            | Fungsi                                                                     |
| ----------------- | -------------------------------------------------------------------------- |
| `deploy.sh`       | Bangun container dari nol: kode, `.env`, Caddyfile, build, jalankan proses |
| `update.sh`       | Pasang kode terbaru + Caddyfile + patch paket PHP ke container yang hidup  |
| `install-deps.sh` | Pasang perkakas sistem (PHP 8.4, Node 22, Caddy, Chromium); idempotent     |
| `watchdog.sh`     | Penjaga `php -S`, pengganti systemd                                        |
| `service-run.sh`  | Entry point runit, disalin ke `/etc/service/resumekan-api/run`             |
| `Caddyfile`       | Konfigurasi Caddy legacy (`:8080` semua interface)                         |

Skrip legacy ini **menolak berjalan** di host Debian/Ubuntu dengan systemd (exit
code 2, dengan pesan pengganti). Override hanya untuk audit:
`ALLOW_LEGACY_ON_SYSTEMD=1 bash deploy/update.sh`.

## Struktur baru (dipakai kedua jalur)

```
deploy/
├── bootstrap.sh              # VPS: siapkan OS dari nol
├── release.sh                # VPS: deploy versi terbaru (aman, ff-only)
├── lib/
│   ├── common.sh             # deteksi OS, helper log, apt, tunggu HTTP
│   ├── legacy.sh             # tolak skrip legacy di VPS systemd
│   ├── php.sh                # Sury (Debian) vs ondrej/php (Ubuntu)
│   ├── php-upgrade.sh        # upgrade patch PHP (bootstrap, release, legacy)
│   ├── composer.sh
│   ├── node.sh
│   ├── pdf-browser.sh        # Chromium Debian / Google Chrome Ubuntu
│   ├── caddy.sh
│   ├── tunnel.sh             # cloudflared + token
│   ├── systemd.sh            # render template, daftarkan unit
│   ├── storage.sh            # perbaiki ownership/permission sebelum restart
│   ├── dist.sh               # unduh artifact Build Web
│   └── frontend.sh           # dist + print shell + permission caddy
├── systemd/
│   ├── resumekan-api.service.tpl
│   ├── resumekan-update.service.tpl
│   ├── resumekan-update.timer.tpl
│   └── Caddyfile.tpl
├── .env.production.example
└── (legacy Railway: deploy.sh, update.sh, install-deps.sh,
    watchdog.sh, service-run.sh, Caddyfile)
```

## Pindah VPS berikutnya

1. Pasang Debian 12 (atau Ubuntu 22.04/24.04) minimal.
2. Clone repo lalu jalankan `sudo bash deploy/bootstrap.sh`.
3. Isi `api/.env` (credential baru hasil rotasi; salin dari manajer sandi).
4. `php artisan migrate:status`; kalau berhasil, `php artisan migrate --force`.
5. Arahkan Cloudflare Tunnel ke `http://localhost:8080`, lalu
   `sudo systemctl restart cloudflared`.
6. Aktifkan auto-update bila perlu, lalu pantau
   `systemctl is-active resumekan-api caddy cloudflared`.

Hanya tiga hal yang tidak bisa diotomatiskan: isi `.env`, cek database, dan
arahkan Tunnel. Sisanya ditangani bootstrap.

## Patch keamanan PHP

Container legacy tidak punya systemd, jadi `unattended-upgrades` tidak jalan.
`lib/php-upgrade.sh` dipanggil dari `bootstrap.sh`, `release.sh`, dan skrip
legacy (`deploy.sh` langkah 1b, `install-deps.sh`, `update.sh` langkah 5) supaya
paket PHP tidak tertinggal di versi rentan.

Upgrade ini sengaja tidak pernah menggagalkan deploy: kalau apt gagal (mirror down, jaringan),
skrip mencetak PERINGATAN dan lanjut, supaya situs tidak ikut mati karena urusan paket.
Lewati dengan `SKIP_PHP_UPGRADE=1` kalau perlu deploy tanpa menyentuh paket.

Riwayat nyata: 2026-10-21 keluar PHP 8.4.27 untuk php2shell (CVE-2026-109618, CVE-2026-109619),
sedangkan container masih di 8.4.26 karena tidak ada langkah yang menaikkannya.

### Flag pengerasan `php -S`

Dipakai oleh `systemd/resumekan-api.service.tpl` (VPS) dan `watchdog.sh` (legacy).
Semuanya pengerasan, bukan kebutuhan aplikasi; jangan dihapus tanpa alasan kuat.

| Flag                                | Alasan                                                                                                                                                                                                        |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `expose_php=0`                      | Header `x-powered-by: PHP/8.4.26` membocorkan versi patch persis ke publik, jadi penyerang tahu apakah target sudah dipatch.                                                                                  |
| `session.upload_progress.enabled=0` | Fitur ini tidak dipakai Laravel, tetapi ia jalur producer/consumer yang dipakai php2shell (CVE-2026-109619). Advisory PHP menyebut mematikannya sebagai mitigasi sebagian, bukan pengganti patch.             |
| `max_multipart_body_parts=200`      | Satu request multipart bisa berisi ribuan bagian dan header yang masing-masing memakan memori jauh lebih besar dari bentuk wire-nya. Aplikasi hanya mengirim satu berkas per request, jadi 200 sudah longgar. |

`upload_max_filesize` dan `post_max_size` bukan pengerasan, melainkan penyesuaian fitur Impor CV
(berkas sampai 5 MB).

## Legacy: pakai (Railway)

Di laptop, sekali saja:

```bash
bash deploy/deploy.sh --template
# isi nilai GANTI_ di deploy/.env.production
```

Kirim ke container:

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
hanya mengunduh URL itu lewat `deploy/lib/dist.sh` dan mengekstraknya ke `web/dist`.

Alur kerja jadi: **push ke `main` -> tunggu workflow "Build Web" hijau -> jalankan `update.sh` di container.** Kalau
workflow belum selesai atau URL tidak bisa diunduh, skrip otomatis jatuh ke `pnpm install && pnpm type-check &&
pnpm build:ci` (lambat, tapi tetap jalan).

URL yang dipakai (bisa ditimpa lewat `DIST_URL`):

```
https://github.com/mhdraihanr/ResumeKan/releases/download/dist-latest/web-dist.tar.gz
```

## Yang tidak dilakukan skrip ini

- **Tidak** menjalankan migrasi saat bootstrap.
- **Tidak** memakai `migrate:fresh`. Database Neon tetap utuh antar redeploy.
- **Tidak** meng-commit atau menyimpan secret di repo.
- **Tidak** mengubah DNS/record Cloudflare secara otomatis.
- **Tidak** mengubah `api/bootstrap/app.php` dan `api/app/Providers/AppServiceProvider.php`. Suntingan produksi
  (termasuk `api/app/Services/PdfService.php`) sudah ada di repo sejak commit `31be3af`.
- **Tidak** memasang swap, jam WIB, atau setelan memori kernel. Itu langkah manual di panduan
  [../docs/DEPLOY_VPS_PEMULA.md](../docs/DEPLOY_VPS_PEMULA.md), dan hanya perlu saat server benar-benar baru.
