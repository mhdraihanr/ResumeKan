[Unit]
Description=ResumeKan: cek update tiap 15 menit (pull-based)
Documentation=file:///srv/ResumeKan/docs/DEPLOY_DEBIAN12_CLOUDFLARE_TUNNEL.md

[Timer]
# Setelah boot, tunggu 2 menit dulu agar jaringan dan service lain stabil.
OnBootSec=2min
OnUnitActiveSec=15min
# Beri jeda acak supaya semua server tidak menabrak GitHub API bersamaan.
RandomizedDelaySec=60s
# Jangan mengejar eksekusi yang terlewat saat mesin mati (bisa menumpuk).
Persistent=false
Unit=resumekan-update.service

[Install]
WantedBy=timers.target
