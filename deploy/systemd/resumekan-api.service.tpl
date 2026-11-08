[Unit]
Description=ResumeKan Laravel API (PHP built-in server)
Documentation=file:///srv/ResumeKan/docs/DEPLOY_DEBIAN12_CLOUDFLARE_TUNNEL.md
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User={{USER}}
Group={{USER}}
WorkingDirectory={{APP_DIR}}/api

# Flag -d adalah pengerasan keamanan; alasan per flag ada di
# deploy/README.md bagian "Flag pengerasan php -S".
#   expose_php=0                      : jangan bocorkan versi PHP di header
#   session.upload_progress.enabled=0 : matikan producer/consumer (vektor php2shell)
#   max_multipart_body_parts=200      : batasi jumlah bagian body multipart
#   upload_max_filesize/post_max_size : fitur Impor CV (maks 5 MB)
Environment=PHP_CLI_SERVER_WORKERS={{WORKERS}}

ExecStart=/usr/bin/php -S 127.0.0.1:{{PORT}} \
  -d upload_max_filesize=6M \
  -d post_max_size=8M \
  -d expose_php=0 \
  -d session.upload_progress.enabled=0 \
  -d max_multipart_body_parts=200 \
  -t public public/index.php

Restart=on-failure
RestartSec=5
# Jangan biarkan proses PHP 8.4 melar karena request rusak.
MemoryMax=800M
TasksMax=128

# ReadWritePaths membatasi tulis ke direktori yang memang dipakai Laravel
# (log/session/cache dan upload sementara). Browsershot memakai temp dir
# terpisah (PrivateTmp), jadi tidak perlu dilonggarkan.
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=full
ProtectHome=true
ReadWritePaths={{APP_DIR}}/api/storage {{APP_DIR}}/api/bootstrap/cache

[Install]
WantedBy=multi-user.target
