# Deploy ResumeKan ke Debian 12 melalui Cloudflare Tunnel

Panduan ini untuk VPS Debian 12 biasa di belakang NAT. Ikuti langkah dari atas ke bawah. Ada tiga tempat yang dipakai: **IDwebhost** untuk nameserver domain, **Cloudflare** untuk DNS dan Tunnel, dan **terminal SSH** untuk menyiapkan aplikasi. Jangan melompati pemeriksaan di akhir tiap langkah.

Hostname yang dipakai di sesi deployment ini adalah `resumekan.web.id`. Jika hostname atau nama domain di panelmu berbeda, sesuaikan nilainya secara konsisten pada Cloudflare dan file `.env`.

VPS berada di balik NAT dan inbound web port tidak tersedia. Jangan membuka port 80/443; Cloudflare Tunnel membuat koneksi keluar dari VPS.

> **Penting tentang secret:** kredensial yang pernah muncul di `docs/VPS.md`, `deploy/.env.production`, atau chat harus dianggap bocor. Cabut/ganti semuanya sebelum produksi. Jangan salin nilai lama dari Git history.

## Sebelum mulai

Siapkan:

- Akses ke akun IDwebhost yang mengelola domain `resumekan.web.id`.
- Akun Cloudflare.
- Akses root SSH ke VPS melalui port `21131`.
- Repository ResumeKan dapat di-clone dari VPS.
- Nilai secret baru dari Neon, Cloudinary, AI provider, dan Turnstile. Jangan kirim nilai tersebut ke chat.

VPS yang dipakai dalam panduan ini terdeteksi sebagai Debian 12 dengan `systemd`, 1 vCPU, RAM sekitar 1 GB, swap 1 GB, dan disk 10 GB. Tunnel memakai koneksi keluar, jadi jangan membuka port publik 80/443 dan jangan membuat A record ke IP VPS/NAT.

## Bagian A — Hubungkan domain IDwebhost ke Cloudflare

> Bagian ini dilakukan di browser, bukan terminal SSH.

### A1. Tambahkan domain ke Cloudflare

1. Masuk ke Cloudflare Dashboard.
2. Pilih **Add a site** atau **Onboard a domain**.
3. Masukkan domain persis yang terdaftar di IDwebhost: `resumekan.web.id`.
4. Pilih plan yang ingin dipakai dan lanjutkan.
5. Jika Cloudflare menawarkan pemindaian/impor DNS, tinjau record yang ditemukan. Jangan lanjut sebelum record layanan yang memang dipakai, seperti email, tercatat benar. Jangan menyalin record lama yang sudah tidak dipakai.
6. Selesaikan penambahan zone. Cloudflare akan menampilkan dua nameserver yang ditetapkan untuk zone itu. Catat persis keduanya; setiap domain dapat memperoleh nameserver berbeda.

Menambahkan domain ke Cloudflare tidak memindahkan registrasi domain dari IDwebhost. Yang diubah nanti hanya layanan DNS authoritative-nya.

### A2. Ganti nameserver di IDwebhost

1. Buka Client Area IDwebhost dan masuk ke menu pengelolaan domain `resumekan.web.id`.
2. Buka menu **Nameserver**, **Kelola Nameserver**, atau menu dengan nama sejenis.
3. Pilih nameserver khusus/custom, lalu ganti nameserver yang sekarang dengan **dua nameserver persis dari Cloudflare**. Jangan isi alamat IP dan jangan gunakan nameserver dari contoh atau domain lain.
4. Simpan perubahan.
5. Kembali ke Cloudflare Dashboard dan tunggu sampai zone `resumekan.web.id` berstatus **Active**. Status Pending berarti delegasi belum terdeteksi; periksa kembali nameserver di IDwebhost sebelum lanjut.

Perubahan menu IDwebhost dapat berbeda menurut tampilan akun. Jika menu nameserver tidak tersedia, hubungi dukungan IDwebhost dan minta bantuan mengganti nameserver domain ke dua nilai yang Cloudflare berikan. Jangan mengubah DNS record satu per satu di IDwebhost untuk full setup ini.

### A3. Buat hostname publik pada Tunnel

1. Di Cloudflare, buka **Networking → Tunnels** (pada tampilan lain: **Zero Trust → Networks → Tunnels**).
2. Pilih Tunnel yang sudah terhubung ke VPS.
3. Buka tab **Routes** dan pilih **Add route → Published application**. Jika dashboard masih menampilkan **Public Hostnames**, gunakan halaman itu untuk menambah hostname.
4. Masukkan hostname publik `resumekan.web.id`. Jika form meminta **Subdomain** dan **Domain** terpisah, pilih domain `resumekan.web.id` lalu biarkan Subdomain kosong. Jangan pilih `web.id` kecuali memang domain terdaftarmu `web.id`.
5. Pilih service type/protocol **HTTP** dan isi service URL `http://localhost:8080`.
6. Simpan route. Route menghubungkan hostname publik ke Caddy di VPS; Caddy selanjutnya meneruskan API ke Laravel.

Saat route dibuat lewat Dashboard dengan full setup, Cloudflare otomatis membuat DNS record untuk Tunnel. Jangan membuat record manual dulu.

### A4. Pastikan DNS record ada

1. Buka zone domain utama di Cloudflare → **DNS → Records**.
2. Cari record hostname `resumekan.web.id`. Jika zone memang `resumekan.web.id`, itu root zone dan bisa tampil sebagai `@`. Jika zone yang dipakai adalah domain induk `web.id`, record-nya bernama `resumekan`. Jangan membuat zone `web.id` kecuali kamu memang memiliki domain `web.id`.
3. Pastikan record bertipe **CNAME**, targetnya tunnel Cloudflare (`<UUID-TUNNEL>.cfargotunnel.com`), dan status proxy aktif/awan oranye.
4. Kalau record muncul setelah menyimpan route, jangan tambah record kedua.
5. Kalau record tetap kosong, pastikan route tersimpan di tunnel yang benar dan zone yang dipilih benar serta status zone **Active**. Jika full setup dan Dashboard tetap tidak membuat record, tambahkan manual CNAME: **Name** `@` untuk zone `resumekan.web.id` atau `resumekan` untuk zone induk `web.id`; **Target** `<UUID-TUNNEL>.cfargotunnel.com`; **Proxy status** Proxied; **TTL** Auto. Gunakan Tunnel ID dari halaman tunnel milikmu. Hapus konflik untuk hostname yang sama hanya setelah memastikan record itu bukan record email atau layanan lain. Jangan arahkan record ke IP VPS.

Jika domain tetap menggunakan nameserver IDwebhost (partial DNS setup), record dikelola di IDwebhost, bukan di Cloudflare. Full setup Cloudflare yang dipakai panduan ini mensyaratkan nameserver domain di IDwebhost diarahkan ke Cloudflare. Ikuti status dan detail di Dashboard; jangan mencampur kedua metode.

## Bagian B — Masuk ke VPS

Di terminal komputer, jalankan:

```bash
ssh -p 21131 root@31.58.103.5
```

Masukkan password di prompt terminal, bukan di chat. Saat berhasil, prompt biasanya berakhir dengan `root@...:~#`. Perintah yang mengubah paket sistem, Caddy, atau unit `systemd` harus dijalankan sebagai `root`. Perintah Composer dan Artisan aplikasi dijalankan sebagai user `resumekan`.

Jangan menjalankan `deploy/install-deps.sh` atau `deploy/deploy.sh`: keduanya dibuat untuk lingkungan lama dan tidak cocok untuk VPS Debian ini.

## Bagian C — Pasang dependency sistem

Jalankan subbagian berikut dari sesi SSH sebagai `root`.

### C1. Paket dasar

```bash
apt-get update
apt-get install -y ca-certificates curl gnupg git unzip zip acl apt-transport-https
```

### C2. PHP 8.4

Project meminta PHP `^8.3`; PHP 8.2 bawaan Debian 12 tidak memenuhi syarat. VPS ini menggunakan repository pihak ketiga Sury. Repo itu bukan repo resmi Debian, sehingga pembaruannya perlu dipelihara.

Cara `extrepo enable sury` tidak berhasil pada VPS ini. Cara manual yang berhasil:

```bash
workdir="$(mktemp -d)"
cd "$workdir"
curl -fsSLO https://packages.sury.org/debsuryorg-archive-keyring.deb
apt-get install -y ./debsuryorg-archive-keyring.deb
. /etc/os-release
printf 'Types: deb deb-src\nURIs: https://packages.sury.org/php\nSuites: %s\nComponents: main\nSigned-By: /usr/share/keyrings/debsuryorg-archive-keyring.gpg\n' "$VERSION_CODENAME" > /etc/apt/sources.list.d/sury-php.sources
apt-get update
apt-cache policy php8.4-cli
```

Berhenti jika `apt-cache policy` tidak menampilkan kandidat PHP 8.4 dari `packages.sury.org` untuk Bookworm. Jika kandidat benar, pasang PHP-FPM, CLI dan ekstensi:

```bash
apt-get install -y php8.4-cli php8.4-fpm php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd
update-alternatives --set php /usr/bin/php8.4
php -v
php -m | grep -E '^(bcmath|curl|dom|gd|intl|mbstring|pdo_pgsql|pgsql|xml|zip)$'
systemctl is-active php8.4-fpm
```

Harus terlihat PHP `8.4.x`, modul dalam daftar, dan status FPM `active`. Jangan expose port FastCGI ke publik.

### C3. Composer

Sebagai root, unduh installer sementara dan verifikasi SHA-384 sebelum memasang:

```bash
EXPECTED_CHECKSUM="$(curl -fsSL https://composer.github.io/installer.sig)"
curl -fsSLo /tmp/composer-setup.php https://getcomposer.org/installer
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
  rm -f /tmp/composer-setup.php
  echo 'Composer installer checksum mismatch' >&2
  exit 1
fi
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f /tmp/composer-setup.php
composer --version
```

### C4. Node.js dan pnpm

Frontend dibuild lewat GitHub Actions agar VPS 1 vCPU tidak terbebani build. Node/npm diperlukan untuk dependency Puppeteer aplikasi.

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs
corepack enable
corepack prepare pnpm@10 --activate
node --version
npm --version
pnpm --version
```

### C5. Font dan browser PDF

Pasang font, lalu cek apakah ada browser:

```bash
apt-get install -y fonts-liberation fonts-noto-core fonts-dejavu-core
command -v chromium || command -v chromium-browser || command -v google-chrome-stable || echo 'Browser belum terpasang'
```

Jika tidak ditemukan, PDF belum siap. Jangan lanjutkan dengan menganggap ekspor PDF berfungsi; pasang browser yang didukung Debian 12 dan lakukan tes render sebelum mengaktifkan fitur PDF.

### C6. Caddy

Jalankan sebagai root. Jika Caddy sudah terpasang, lewati pemasangan paket dan lanjutkan ke konfigurasi di Bagian H.

```bash
apt-get install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
  | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
  > /etc/apt/sources.list.d/caddy-stable.list
chmod o+r /usr/share/keyrings/caddy-stable-archive-keyring.gpg \
  /etc/apt/sources.list.d/caddy-stable.list
apt-get update
apt-get install -y caddy
caddy version
systemctl is-active caddy
```

## Bagian D — Clone repository dan pasang PHP dependencies

Buat user aplikasi dan direktori hanya jika belum ada. Dari root:

```bash
id resumekan >/dev/null 2>&1 || adduser --disabled-password --gecos '' resumekan
install -d -o resumekan -g resumekan -m 750 /srv/ResumeKan
```

Jika `/srv/ResumeKan` sudah berisi repository, jangan clone ulang. Pindah ke user aplikasi:

```bash
su - resumekan
```

Di bawah user `resumekan`, jalankan:

```bash
if [ ! -d /srv/ResumeKan/.git ]; then
  git clone --branch main https://github.com/mhdraihanr/ResumeKan.git /srv/ResumeKan
fi
cd /srv/ResumeKan/api
composer install --no-dev --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
```

Keduanya harus berakhir tanpa error. Jika repo sudah ada tetapi Git melaporkan perubahan lokal, jangan reset atau hapus perubahan; berhenti dan periksa status Git.

## Bagian E — Buat `.env` produksi

Pastikan prompt terminal adalah `resumekan@...` dan direktori `/srv/ResumeKan/api`. Buat key baru satu kali:

```bash
cd /srv/ResumeKan/api
php artisan key:generate --show
```

Salin nilai yang tampil langsung ke editor server—jangan ke chat atau Git. Buat file:

```bash
umask 077
nano /srv/ResumeKan/api/.env
```

Isi dengan template di bawah. Ganti setiap `GANTI_...` dengan credential hasil rotasi. Konfigurasi PostgreSQL mendukung `DB_URL` maupun field terpisah. Untuk mengikuti panduan ini, isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `DB_SSLMODE`; jangan menaruh credential pada baris `DB_URL`.

```env
APP_NAME=ResumeKan
APP_ENV=production
APP_KEY=GANTI_DENGAN_KEY_BARU
APP_DEBUG=false
APP_URL=https://resumekan.web.id
FRONTEND_URL=https://resumekan.web.id
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning
DB_CONNECTION=pgsql
DB_HOST=GANTI_HOST_NEON
DB_PORT=5432
DB_DATABASE=GANTI_NAMA_DATABASE
DB_USERNAME=GANTI_USERNAME_BARU
DB_PASSWORD=GANTI_PASSWORD_BARU
DB_SSLMODE=require
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_PATH=/
SESSION_DOMAIN=resumekan.web.id
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=resumekan.web.id
AI_API_KEY=GANTI_AI_API_KEY
AI_BASE_URL=GANTI_AI_BASE_URL
AI_MODEL=GANTI_NAMA_MODEL
AI_TIMEOUT=30
AI_THROTTLE_PER_MINUTE=5
AI_PARSE_THROTTLE_PER_MINUTE=3
CLOUDINARY_CLOUD_NAME=GANTI_CLOUD_NAME
CLOUDINARY_API_KEY=GANTI_CLOUDINARY_API_KEY
CLOUDINARY_API_SECRET=GANTI_CLOUDINARY_API_SECRET
CLOUDINARY_FOLDER=cvs
CV_MAX_PER_USER=10
QUEUE_CONNECTION=sync
CACHE_STORE=database
FILESYSTEM_DISK=local
BROADCAST_CONNECTION=log
MAIL_MAILER=log
REGISTER_PER_MINUTE=5
REGISTER_PER_HOUR=20
TURNSTILE_SITE_KEY=GANTI_TURNSTILE_SITE_KEY
TURNSTILE_SECRET_KEY=GANTI_TURNSTILE_SECRET_KEY
TURNSTILE_TIMEOUT=5
```

Simpan di Nano: tekan `Ctrl+O`, `Enter`, lalu `Ctrl+X`. Kunci permission dan periksa placeholder tanpa mencetak nilai rahasia:

```bash
chmod 600 /srv/ResumeKan/api/.env
grep -q 'GANTI_' /srv/ResumeKan/api/.env && echo 'Masih ada placeholder' || echo 'Placeholder tidak ditemukan'
stat -c '%U:%G %a %n' /srv/ResumeKan/api/.env
```

Owner harus `resumekan` dan mode `600`. Jika perintah menyatakan placeholder tersisa, edit lagi sebelum lanjut.

Uji koneksi dan status migrasi tanpa mengubah schema:

```bash
cd /srv/ResumeKan/api
php artisan config:clear
php artisan migrate:status
```

Jika error menyebut `Connection: sqlite`, periksa `DB_CONNECTION=pgsql` di `.env` dan jalankan ulang `config:clear`. Jika error `could not find driver`, pastikan `php -m` memuat `pdo_pgsql`. Jika error autentikasi atau timeout, cek kembali nilai Neon dan akses jaringan; jangan kirim `.env` ke siapa pun.

Hanya setelah `migrate:status` berhasil dan database Neon yang benar sudah dipastikan, jalankan:

```bash
php artisan migrate --force
php artisan config:cache
```

Migrasi dapat mengubah schema. Jangan jalankan `migrate:fresh` atau perintah penghapus tabel.

## Bagian F — Pasang frontend dan print shell

Frontend sebaiknya diambil dari artifact **Build Web** yang sukses di GitHub Actions. Di VPS sebagai `resumekan`, cek apakah hasil build sudah tersedia:

```bash
ls -l /srv/ResumeKan/web/dist/index.html /srv/ResumeKan/web/dist/print.html
```

Jika file belum ada, jalankan helper `fetch_dist` dari `deploy/lib/dist.sh` setelah memastikan artifact yang diunduh berasal dari build repository yang benar. Jika harus build langsung di VPS, dari `/srv/ResumeKan/web` jalankan:

```bash
pnpm install --frozen-lockfile
pnpm type-check
pnpm build:ci
```

Build harus menghasilkan `web/dist/index.html` dan `web/dist/print.html`. Setelah itu, sebagai root pasang print shell:

```bash
install -d -o resumekan -g resumekan -m 755 /srv/ResumeKan/api/public/print
install -o resumekan -g resumekan -m 644 /srv/ResumeKan/web/dist/print.html /srv/ResumeKan/api/public/print/index.html
```

Pastikan Caddy dapat membaca file build. Sebagai root:

```bash
chmod o+x /srv/ResumeKan
find /srv/ResumeKan/web/dist -type d -exec chmod 755 {} +
find /srv/ResumeKan/web/dist -type f -exec chmod 644 {} +
runuser -u caddy -- test -r /srv/ResumeKan/web/dist/index.html && echo 'Caddy dapat membaca frontend'
```

## Bagian G — Buat unit Laravel dengan systemd

> **Cara cepat:** mulai rilis ini, seluruh Bagian C–H bisa dijalankan otomatis:
> `sudo bash deploy/bootstrap.sh` (tambahkan `--check` untuk audit saja). Bagian
> di bawah tetap berguna untuk memahami apa yang dipasang atau memperbaiki manual.

Bagian ini memakai PHP built-in server karena project saat ini belum memiliki konfigurasi PHP-FPM+Caddy lengkap. Ini cukup untuk memulai di VPS kecil; jangan gunakan empat worker pada RAM sekitar 1 GB.

Keluar dari user `resumekan`, lalu pastikan prompt root:

```bash
exit
```

Sebagai root, buat unit service:

```bash
nano /etc/systemd/system/resumekan-api.service
```

Isi (sama dengan `deploy/systemd/resumekan-api.service.tpl`; `WORKERS=2` cukup untuk 1 vCPU + RAM 1 GB):

```ini
[Unit]
Description=ResumeKan Laravel API (PHP built-in server)
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=resumekan
Group=resumekan
WorkingDirectory=/srv/ResumeKan/api

# Flag -d adalah pengerasan keamanan; alasan per flag ada di deploy/README.md.
Environment=PHP_CLI_SERVER_WORKERS=2
ExecStart=/usr/bin/php -S 127.0.0.1:8000 \
  -d upload_max_filesize=6M \
  -d post_max_size=8M \
  -d expose_php=0 \
  -d session.upload_progress.enabled=0 \
  -d max_multipart_body_parts=200 \
  -t public public/index.php

Restart=on-failure
RestartSec=5
MemoryMax=800M
TasksMax=128

NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=full
ProtectHome=true
ReadWritePaths=/srv/ResumeKan/api/storage /srv/ResumeKan/api/bootstrap/cache

[Install]
WantedBy=multi-user.target
```

Simpan (`Ctrl+O`, `Enter`, `Ctrl+X`), lalu aktifkan sebagai root:

```bash
systemctl daemon-reload
systemctl enable --now resumekan-api
systemctl status resumekan-api --no-pager
```

`Active: active (running)` berarti service berjalan. Jika gagal, jangan lanjut; lihat log dengan `journalctl -u resumekan-api -n 50 --no-pager` dan perbaiki error yang ditunjukkan.

## Bagian H — Konfigurasikan Caddy

Bagian ini seluruhnya dilakukan sebagai root. Caddy harus menerima HTTP hanya dari loopback `127.0.0.1:8080`; Cloudflare Tunnel yang memberi HTTPS publik.

Buat backup sekali saja. Jika backup sudah ada, jangan timpa:

```bash
test -e /etc/caddy/Caddyfile.before-http-origin || cp -a /etc/caddy/Caddyfile /etc/caddy/Caddyfile.before-http-origin
nano /etc/caddy/Caddyfile
```

Isi Caddyfile:

```caddyfile
{
	auto_https off
	admin 127.0.0.1:2019
}

http://:8080 {
	bind 127.0.0.1
	root * /srv/ResumeKan/web/dist
	encode gzip zstd

	handle /api/* {
		request_body {
			max_size 10MB
		}
		reverse_proxy 127.0.0.1:8000
	}

	handle /sanctum/* {
		request_body {
			max_size 1MB
		}
		reverse_proxy 127.0.0.1:8000
	}

	handle /up {
		reverse_proxy 127.0.0.1:8000
	}

	handle {
		try_files {path} /index.html
		file_server
	}
}
```

Jangan tulis `127.0.0.1:8080` sebagai alamat site. Gunakan `http://:8080` dan `bind 127.0.0.1`; format ini memaksa HTTP tanpa TLS pada origin dan tetap menerima Host `resumekan.web.id` dari Tunnel.

Simpan Caddyfile, lalu validasi dan reload sebagai root:

```bash
caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile && systemctl reload caddy
systemctl is-active caddy
ss -lntp | grep ':8080'
```

Harus terlihat Caddy `active` dan listener `127.0.0.1:8080`. Jika validasi gagal, jangan reload; kirim pesan error tanpa secret.

## Bagian I — Tes lokal, Tunnel, dan domain

Jalankan dari VPS. Tes lokal dahulu:

```bash
curl --max-time 5 -i http://127.0.0.1:8000/up
curl --max-time 5 -i -H 'Host: resumekan.web.id' http://127.0.0.1:8080/up
curl --max-time 5 -i -H 'Host: resumekan.web.id' http://127.0.0.1:8080/
```

Hasil yang diharapkan: semua request memberi `HTTP 200`. `/up` mengembalikan halaman status Laravel; `/` mengembalikan halaman frontend, bukan `403` atau halaman Laravel default.

Lalu uji DNS dan hostname publik:

```bash
getent hosts resumekan.web.id
curl --max-time 10 -i https://resumekan.web.id/up
curl --max-time 10 -I https://resumekan.web.id/
```

Jika `getent` tidak menemukan hostname, cek status zone Cloudflare, nameserver di IDwebhost, dan CNAME tunnel. Jika `/up` publik 200 tetapi `/` 403, periksa permission frontend: Caddy berjalan sebagai user `caddy` dan harus bisa membaca semua direktori/file di `web/dist`. Jika DNS belum resolve di VPS tetapi situs terbuka dari jaringan lain, jangan ubah record membabi buta; coba lagi dari resolver publik atau periksa resolver VPS.

Setelah halaman utama terbuka, uji register, login/logout, refresh, simpan CV, upload Cloudinary, AI, lalu PDF setelah browser terpasang. Periksa cookie memakai `Secure` dan browser tidak melaporkan error CORS/CSRF. Setelah reboot, cek ketiga service:

```bash
systemctl is-active resumekan-api caddy cloudflared
```

## Update aplikasi

> **Cara cepat:** `sudo bash deploy/release.sh --if-changed` (lihat `deploy/README.md`).

Jangan jalankan `deploy/update.sh`: script itu memakai `git reset --hard origin/main` dan pengelolaan proses lama. Untuk update, sebagai user `resumekan`, tinjau `git status` dahulu lalu ambil perubahan tanpa membuang perubahan lokal. Jika frontend berubah, tunggu workflow **Build Web** sukses dan pasang artifact baru. Jalankan migrasi pending hanya setelah meninjau migrasi dan memastikan backup database tersedia; lalu `php artisan config:cache` dan restart unit yang berubah.

`deploy/release.sh` melakukan semua itu dengan `git merge --ff-only` (bukan reset), memeriksa kesehatan backend dan Caddy, dan menyimpan patch perubahan lokal di `/root/` bila ada. Untuk update otomatis:

```bash
systemctl enable --now resumekan-update.timer   # cek tiap 15 menit, no-op kalau tidak ada commit baru
systemctl list-timers resumekan-update.timer
journalctl -u resumekan-update -n 50 --no-pager
```

## Hasil dan hal yang belum selesai

Sudah diperiksa langsung di VPS: Debian 12/systemd; PHP CLI/FPM `8.4.27`; Composer `2.11.1`; Git `2.39.5`; Node `22.23.4`; npm `10.9.9`; pnpm `10.34.9`; Caddy `2.11.9`. User `resumekan` dan repository `/srv/ResumeKan` tersedia, Composer install dan `composer check-platform-reqs --no-dev` berhasil.

Pada tes terakhir, Laravel `/up` di port `8000` dan Caddy `/up` di port `8080` memberi HTTP 200. Caddy juga menyajikan `/` secara lokal setelah permission frontend diperbaiki. Tunnel aktif dan route `resumekan.web.id` menunjuk ke `http://localhost:8080`. HTTPS publik pernah memberi HTTP 200 pada `/up`; resolver VPS juga beberapa kali gagal menemukan domain. Uji DNS/HTTPS kembali setelah nameserver dan record dikonfirmasi aktif.

Belum diverifikasi: login/session/CRUD, ekspor PDF (browser belum terpasang), dan apakah semua service otomatis pulih setelah reboot. Jaga credential baru hanya di `.env` server.
