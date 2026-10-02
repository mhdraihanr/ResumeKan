import { fileURLToPath, URL } from "node:url";

import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";
import tailwindcss from "@tailwindcss/vite";

// https://vite.dev/config/
export default defineConfig(({ command }) => ({
  // vue-devtools hanya untuk dev; tidak dipakai di build produksi karena
  // berat dan tidak ada gunanya di bundle rilis.
  plugins: [
    vue(),
    ...(command === "serve" ? [vueDevTools()] : []),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },
  build: {
    // Container produksi (Railway free) hanya punya 0.5 CPU. Dua default Vite 8
    // membebani CPU secara sia-sia di sana:
    //   - reportCompressedSize: true -> menghitung gzip SETIAP chunk (serial, berat)
    //   - minify bawaan 'oxc' -> sudah paling cepat; jangan diturunkan ke terser
    // Matikan hitung gzip: hasil bundle tidak berubah, hanya laporan ukuran yang hilang.
    reportCompressedSize: false,
    rollupOptions: {
      input: {
        main: fileURLToPath(new URL("./index.html", import.meta.url)),
        print: fileURLToPath(new URL("./print.html", import.meta.url)),
      },
    },
  },
  server: {
    proxy: {
      "/api": {
        target: "http://localhost:8000",
        changeOrigin: false,
      },
      "/sanctum": {
        target: "http://localhost:8000",
        changeOrigin: false,
      },
    },
  },
}));
