// Signed upload ke Cloudinary. cloud_name/api_key diterima dari backend
// agar credential tidak pernah bocor ke bundle browser.
const FILE_SIZE_LIMIT = 2 * 1024 * 1024; // 2 MB

// Minta signature dari backend, lalu upload file langsung dari browser ke Cloudinary.
export async function uploadPhoto(file: File): Promise<string> {
  if (!file.type.startsWith("image/")) {
    throw new Error("File harus berupa gambar.");
  }
  if (file.size > FILE_SIZE_LIMIT) {
    throw new Error("Ukuran gambar maksimal 2 MB.");
  }

  const sigRes = await fetch("/api/v1/upload-signature", {
    method: "POST",
    headers: {
      Accept: "application/json",
      "X-XSRF-TOKEN": xsrfToken(),
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "include",
  });
  if (!sigRes.ok) {
    throw new Error("Gagal mengambil tanda tangan upload.");
  }
  const sig = (await sigRes.json()) as {
    cloud_name: string;
    api_key: string;
    timestamp: string;
    signature: string;
    folder: string;
  };

  const form = new FormData();
  form.append("file", file);
  form.append("api_key", sig.api_key);
  form.append("timestamp", sig.timestamp);
  form.append("signature", sig.signature);
  if (sig.folder) form.append("folder", sig.folder);

  const res = await fetch(
    `https://api.cloudinary.com/v1_1/${sig.cloud_name}/image/upload`,
    { method: "POST", body: form },
  );
  if (!res.ok) {
    throw new Error("Upload foto gagal. Cek konfigurasi Cloudinary.");
  }
  const data = (await res.json()) as { secure_url?: string };
  if (!data.secure_url) {
    throw new Error("Upload foto gagal. Tidak ada URL yang dikembalikan.");
  }
  return data.secure_url;
}

function xsrfToken(): string {
  const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return m ? decodeURIComponent(m[1]!) : "";
}
