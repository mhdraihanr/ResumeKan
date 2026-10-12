<script setup lang="ts">
import { computed, ref } from "vue";
import {
  CircleAlert,
  CircleCheck,
  FileUp,
  Loader2,
  ScanText,
} from "lucide-vue-next";
import { cvApi } from "@/api/cv";
import type { CvData } from "@/types/cv";

/**
 * Panel impor CV: pilih berkas PDF/DOCX, validasi di klien, unggah ke
 * `POST /api/v1/ai/parse-cv`, lalu pancarkan hasilnya ke induk (`CvFormView`).
 * Panel ini TIDAK menyentuh state form sendiri: pengisian form dilakukan induk
 * supaya tidak ada dua sumber kebenaran untuk `CvData`.
 */
const emit = defineEmits<{
  imported: [payload: { data: CvData; warnings: string[] }];
  error: [message: string];
}>();

/** Batas klien, cerminan `ParseCvRequest` (5 MB). Server tetap penentu akhir. */
const MAX_BYTES = 5 * 1024 * 1024;
const ALLOWED_EXT = [".pdf", ".docx"];

type Status = "idle" | "loading" | "error" | "success";

const status = ref<Status>("idle");
const errorMsg = ref("");
const fileName = ref("");
const fileInput = ref<HTMLInputElement | null>(null);

const loading = computed(() => status.value === "loading");

function extOf(name: string): string {
  const dot = name.lastIndexOf(".");
  return dot >= 0 ? name.slice(dot).toLowerCase() : "";
}

function fail(msg: string) {
  status.value = "error";
  errorMsg.value = msg;
  emit("error", msg);
}

/** Terjemahkan error API jadi pesan yang menyebut sebab dan langkah berikutnya. */
function pesanError(err: unknown): string {
  const e = err as { status?: number; message?: string };
  switch (e.status) {
    case 401:
      return "Sesi berakhir. Silakan masuk lagi.";
    case 422:
      return e.message || "Berkas tidak bisa dibaca.";
    case 429:
      return "Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.";
    case 502:
      return "Layanan AI sedang bermasalah. Coba lagi.";
    case 503:
      return "Fitur AI belum dikonfigurasi. Hubungi admin.";
    default:
      return e.message || "Gagal membaca CV. Coba lagi.";
  }
}

async function onPick(e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  fileName.value = file.name;

  // Validasi klien dulu: ekstensi lalu ukuran, sebelum menyentuh jaringan.
  if (!ALLOWED_EXT.includes(extOf(file.name))) {
    fail("Format tidak didukung. Upload berkas PDF atau DOCX.");
    input.value = "";
    return;
  }
  if (file.size > MAX_BYTES) {
    fail("Ukuran berkas maksimal 5 MB.");
    input.value = "";
    return;
  }

  status.value = "loading";
  errorMsg.value = "";
  try {
    const hasil = await cvApi.parseCv(file);
    status.value = "success";
    emit("imported", hasil);
  } catch (err) {
    fail(pesanError(err));
  } finally {
    input.value = "";
  }
}

function reset() {
  status.value = "idle";
  errorMsg.value = "";
  fileName.value = "";
}
</script>

<template>
  <section
    class="rounded-base border-2 border-ink bg-white p-4 shadow-shadow dark:border-border dark:bg-secondary-background"
    aria-labelledby="import-cv-heading"
    :aria-busy="loading"
  >
    <div class="flex items-start gap-3">
      <span
        class="flex size-9 shrink-0 items-center justify-center rounded-base border-2 border-ink bg-powder text-ink dark:border-border"
        aria-hidden="true"
      >
        <ScanText class="size-4" />
      </span>
      <div class="min-w-0">
        <h2
          id="import-cv-heading"
          class="text-sm font-black text-ink dark:text-foreground"
        >
          Impor CV
        </h2>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-300">
          Sudah punya CV? Unggah berkasnya, form diisi otomatis. Kamu tetap
          mereview sebelum menyimpan.
        </p>
      </div>
    </div>

    <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-300">
      Format didukung: PDF atau DOCX, maks 5 MB dan maks 5 halaman. PDF hasil
      scan (gambar) tidak terbaca.
    </p>

    <div class="mt-3 flex flex-wrap items-center gap-2">
      <input
        ref="fileInput"
        type="file"
        accept=".pdf,.docx"
        class="sr-only"
        :disabled="loading"
        @change="onPick"
      />
      <button
        type="button"
        :disabled="loading"
        class="inline-flex h-9 items-center gap-1.5 rounded-base border-2 border-ink bg-white px-3 text-xs font-bold text-ink transition-colors hover:bg-powder focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink disabled:cursor-not-allowed disabled:border-ink/30 disabled:text-ink/40 disabled:hover:bg-white dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-white/15 dark:disabled:border-border/40 dark:disabled:text-foreground/40 dark:disabled:hover:bg-background"
        @click="fileInput?.click()"
      >
        <FileUp class="size-3.5" aria-hidden="true" />
        Pilih berkas
      </button>

      <span
        v-if="fileName"
        class="min-w-0 max-w-[14rem] truncate text-xs text-slate-600 dark:text-slate-300"
        :title="fileName"
      >
        {{ fileName }}
      </span>

      <button
        v-if="status !== 'idle'"
        type="button"
        class="text-xs font-medium text-slate-500 underline hover:text-ink dark:text-slate-300 dark:hover:text-foreground"
        @click="reset"
      >
        Reset
      </button>
    </div>

    <p
      v-if="loading"
      role="status"
      aria-live="polite"
      class="mt-3 inline-flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300"
    >
      <Loader2 class="size-3.5 animate-spin" aria-hidden="true" />
      Membaca CV...
    </p>

    <p
      v-else-if="status === 'error'"
      role="alert"
      class="mt-3 flex items-start gap-1.5 rounded-base border-2 border-red-600 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 dark:border-red-500 dark:bg-red-900/20 dark:text-red-300"
    >
      <CircleAlert class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
      <span>{{ errorMsg }}</span>
    </p>

    <p
      v-else-if="status === 'success'"
      role="status"
      class="mt-3 flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300"
    >
      <CircleCheck class="size-3.5" aria-hidden="true" />
      CV berhasil dibaca.
    </p>
  </section>
</template>
