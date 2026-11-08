# Satu host: tampilan dan API di origin yang sama.
# Cloudflare Tunnel yang menyediakan HTTPS publik; Caddy melayani HTTP lokal.
# ALASAN format `http://:8080` (bukan `127.0.0.1:8080`): `auto_https off` saja
# tidak menonaktifkan TLS implicit kalau site address berisi host/IP, dan
# matcher Host akan memblokir Host dari Tunnel. Format ini memaksa plain HTTP
# dan menerima Host apa pun, sementara `bind 127.0.0.1` membatasi listener ke
# loopback sehingga port tidak terekspos ke jaringan lain.
{
	auto_https off
	admin 127.0.0.1:2019
}

http://:{{PORT}} {
	bind 127.0.0.1
	root * {{APP_DIR}}/web/dist
	encode gzip zstd

	handle /api/* {
		# Pembatas body: fitur Impor CV butuh 5 MB; php -S memakai post_max_size=8M.
		# Batas 10MB memberi ruang pesan 413/422 dari Laravel, bukan dari Caddy.
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

	# SPA fallback harus di handle{} terakhir: handle bersifat saling eksklusif.
	handle {
		try_files {path} /index.html
		file_server
	}
}
