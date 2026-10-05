<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useDarkMode } from "@/composables/useDarkMode";

/**
 * Pembungkus Cloudflare Turnstile (render eksplisit).
 *
 * Kenapa render eksplisit, bukan widget otomatis: kita perlu mengatur ulang
 * widget setelah submit gagal, karena token Turnstile sekali pakai. Script
 * Cloudflare dimuat sekali (dibagi lewat `loadScript`) supaya membuka ulang
 * halaman register tidak menumpuk tag <script>.
 */

interface TurnstileOptions {
  sitekey: string;
  theme: "light" | "dark" | "auto";
  size: "normal" | "flexible" | "compact";
  callback: (token: string) => void;
  "error-callback": () => void;
  "expired-callback": () => void;
}

interface TurnstileApi {
  render: (el: HTMLElement, opts: TurnstileOptions) => string;
  reset: (id?: string) => void;
  remove: (id?: string) => void;
}

declare global {
  interface Window {
    turnstile?: TurnstileApi;
    onTurnstileLoad?: () => void;
  }
}

const props = defineProps<{ siteKey: string }>();
const emit = defineEmits<{
  verified: [token: string];
  expired: [];
}>();

const { isDark } = useDarkMode();
const container = ref<HTMLElement | null>(null);
const failed = ref(false);
let widgetId: string | null = null;

// `onload=onTurnstileLoad` wajib: dengan render eksplisit Cloudflare hanya
// memanggil callback yang namanya disebut di query string. Tanpa itu script
// selesai dimuat tapi kita tidak pernah tahu, sehingga render() tak pernah jalan.
const SCRIPT_SRC =
  "https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad&render=explicit";

// Promise tunggal: beberapa instance (atau mount ulang) berbagi satu muat script.
let scriptPromise: Promise<void> | null = null;

function loadScript(): Promise<void> {
  if (window.turnstile) return Promise.resolve();
  if (scriptPromise) return scriptPromise;

  scriptPromise = new Promise<void>((resolve, reject) => {
    // Callback global harus terpasang SEBELUM tag <script> disisipkan.
    window.onTurnstileLoad = () => resolve();

    const s = document.createElement("script");
    s.src = SCRIPT_SRC;
    s.async = true;
    s.defer = true;
    s.onerror = () => {
      scriptPromise = null;
      reject(new Error("Gagal memuat Turnstile."));
    };
    document.head.appendChild(s);

    // Cadangan: kalau script sudah ada di cache browser, callback `onload` bisa
    // terlewat. Pantau global `window.turnstile` sampai muncul.
    const startedAt = Date.now();
    const poll = window.setInterval(() => {
      if (window.turnstile) {
        window.clearInterval(poll);
        resolve();
      } else if (Date.now() - startedAt > 10000) {
        window.clearInterval(poll);
        scriptPromise = null;
        reject(new Error("Turnstile timeout."));
      }
    }, 50);
  });

  return scriptPromise;
}

function render() {
  if (!container.value || !window.turnstile) return;

  // Bersihkan widget lama dulu supaya tidak ada dua iframe bertumpuk.
  if (widgetId && window.turnstile) {
    window.turnstile.remove(widgetId);
    widgetId = null;
  }

  widgetId = window.turnstile.render(container.value, {
    sitekey: props.siteKey,
    theme: isDark() ? "dark" : "light",
    // `flexible`: lebar 100% (min 300px), sejajar dengan field lain dan tetap
    // responsif di layar sempit — berbeda dari `normal` yang terkunci 300px.
    size: "flexible",
    callback: (token) => emit("verified", token),
    "expired-callback": () => emit("expired"),
    "error-callback": () => {
      failed.value = true;
    },
  });
}

onMounted(async () => {
  if (!props.siteKey) return;
  try {
    await loadScript();
    render();
  } catch {
    failed.value = true;
  }
});

// Ganti tema Turnstile saat user menekan tombol gelap/terang.
watch(
  () => isDark(),
  () => {
    if (props.siteKey && window.turnstile) render();
  },
);

onBeforeUnmount(() => {
  if (widgetId && window.turnstile) window.turnstile.remove(widgetId);
});

/** Token sekali pakai: panggil setelah submit gagal agar user dapat token baru. */
function reset() {
  failed.value = false;
  if (widgetId && window.turnstile) window.turnstile.reset(widgetId);
}

defineExpose({ reset });
</script>

<template>
  <div class="w-full">
    <div ref="container" class="w-full" />
    <p
      v-if="failed"
      class="mt-2 text-xs font-medium text-error dark:text-red-300"
    >
      Verifikasi keamanan gagal dimuat. Muat ulang halaman untuk mencoba lagi.
    </p>
  </div>
</template>
