[Unit]
Description=ResumeKan: periksa rilis baru dan deploy (pull-based)
Documentation=file:///srv/ResumeKan/docs/DEPLOY_DEBIAN12_CLOUDFLARE_TUNNEL.md
After=network-online.target
Wants=network-online.target

# Service ini TIDAK berjalan otomatis saat boot; dijalankan oleh
# resumekan-update.timer. Aman untuk dijalankan manual:
#   systemctl start resumekan-update.service
# Lihat log:
#   journalctl -u resumekan-update -n 100 --no-pager

[Service]
Type=oneshot
User=root
WorkingDirectory={{APP_DIR}}
# Memanggil release.sh yang sudah ada di repo. `main` adalah branch default.
# Argumen:
#   --if-changed      : tidak melakukan apa pun kalau SHA lokal == origin/main
#   --from-timer      : tanda khusus (mis. melewati prompt interaktif)
ExecStart=/usr/bin/env bash {{APP_DIR}}/deploy/release.sh --if-changed --from-timer
# Timer tidak boleh menumpuk; kalau update sebelumnya masih jalan, tunggu.
TimeoutStartSec=20min
Nice=10
IOSchedulingClass=best-effort
IOSchedulingPriority=7

# Tulis log ringkas untuk audit.
StandardOutput=journal
StandardError=journal

# Batasi kerusakan bila ada bug di release.sh.
NoNewPrivileges=false
