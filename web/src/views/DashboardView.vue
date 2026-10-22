<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import {
  Download,
  FileText,
  Languages,
  Loader2,
  Pencil,
  Trash2,
} from "lucide-vue-next";
import { useAuthStore } from "@/stores/auth";
import { useCvStore } from "@/stores/cv";
import { cvApi } from "@/api/cv";
import type { Cv } from "@/types/cv";
import CvThumb from "@/components/dashboard/CvThumb.vue";
import { ConfirmDialog } from "@/components/confirm";

const auth = useAuthStore();
const cvStore = useCvStore();
const router = useRouter();

onMounted(() => cvStore.fetchList());

const MAX_CV = 10;

function fmtDate(s?: string) {
  if (!s) return null;
  const d = new Date(s);
  if (Number.isNaN(d.getTime())) return null;
  return d.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

const total = computed(() => cvStore.list.length);
const readyCount = computed(
  () => cvStore.list.filter((cv) => cv.is_complete !== false).length,
);
const atLimit = computed(() => total.value >= MAX_CV);

const initial = computed(() =>
  (auth.user?.name ?? "?").trim().charAt(0).toUpperCase(),
);

const memberSince = computed(() => fmtDate(auth.user?.created_at));

const TEMPLATES: Record<Cv["template"], { label: string; badgeClass: string }> =
  {
    modern: { label: "Modern", badgeClass: "bg-navy text-white" },
    classic: {
      label: "Classic",
      badgeClass: "bg-ink text-white dark:bg-foreground dark:text-ink",
    },
    neon: { label: "Neon", badgeClass: "bg-powder text-ink" },
  };

// Dialog menggantikan confirm() bawaan: judul memuat nama CV, tombol destruktif
// berlabel teks, dan fokus kembali ke pemicu setelah selesai.
const pendingDelete = ref<Cv | null>(null);
const deleting = ref(false);
const listRef = ref<HTMLUListElement | null>(null);
const newCvRef = ref<HTMLAnchorElement | null>(null);

function askDelete(cv: Cv) {
  cvStore.error = null;
  pendingDelete.value = cv;
}

function cancelDelete() {
  if (deleting.value) return;
  pendingDelete.value = null;
}

async function confirmDelete() {
  const cv = pendingDelete.value;
  if (!cv || deleting.value) return;

  // Posisi diambil sebelum list berubah: setelah kartu hilang, fokus harus
  // jatuh ke kartu yang mengisi celahnya, bukan lepas ke <body> (WCAG 2.4.3).
  const index = cvStore.list.findIndex((item) => item.id === cv.id);

  deleting.value = true;
  try {
    await cvStore.remove(cv.id);
    pendingDelete.value = null;
    await moveFocusAfterDelete(index);
  } catch (e) {
    cvStore.error = e instanceof Error ? e.message : "Gagal menghapus CV";
  } finally {
    deleting.value = false;
  }
}

// Daftar kosong tidak punya tombol Hapus lagi, jadi fokus pindah ke CTA
// utama dan pembaca layar mendengar akibatnya lewat `aria-live` di bawah.
async function moveFocusAfterDelete(removedIndex: number) {
  await nextTick();
  const buttons =
    listRef.value?.querySelectorAll<HTMLButtonElement>("[data-cv-delete]");
  const next =
    buttons?.[Math.min(Math.max(removedIndex, 0), buttons.length - 1)] ??
    newCvRef.value;
  next?.focus();
}

const translatingId = ref<number | null>(null);
const translating = computed(() => translatingId.value !== null);
const downloadingPdfId = ref<number | null>(null);
const translatedMessage = ref("");
let pdfAbort: AbortController | null = null;

// Batalkan render PDF yang masih berjalan saat halaman ditinggalkan.
onBeforeUnmount(() => pdfAbort?.abort());

const SECTION_LABEL: Record<string, string> = {
  experiences: "Pengalaman",
  education: "Pendidikan",
  organizations: "Organisasi",
  certificates: "Sertifikat",
  projects: "Proyek",
};

const SECTION_KEY = new RegExp(
  `^data\\.(${Object.keys(SECTION_LABEL).join("|")})\\.`,
);

// `/translate` tidak memvalidasi `data.*`, jadi 422 di sini hanya bentuk tak terduga.
// Yang benar-benar mungkin: 502 layanan dan 429 throttle.
function translateError(e: unknown): string {
  const err = e as Error & { status?: number };

  if (err.status === 502) {
    return "Layanan terjemahan sedang tidak tersedia. Coba lagi sebentar lagi.";
  }

  if (err.status === 429) {
    return "Terlalu banyak permintaan terjemah. Tunggu sebentar lalu coba lagi.";
  }

  if (err.status === 403) {
    return "CV ini bukan milik akun Anda, jadi tidak bisa diterjemahkan.";
  }

  return err.message || "Gagal menerjemahkan CV. Coba lagi.";
}

// Laravel mengirim pesan validasi mentah berbahasa Inggris, jadi yang dibaca
// adalah kunci error, bukan pesannya.
function missingSections(errors: Record<string, string[]>): string[] {
  const sections = new Set<string>();

  for (const key of Object.keys(errors)) {
    const match = key.match(SECTION_KEY);
    if (match) sections.add(SECTION_LABEL[match[1]!]!);
  }

  return [...sections];
}

// Kuota penuh juga memakai 422 (`errors.title`), bukan hanya data belum lengkap.
function isQuotaError(errors: Record<string, string[]>): boolean {
  return Object.values(errors)
    .flat()
    .some((message) => /maksimal\s+\d+\s+cv/i.test(message));
}

function createError(cv: Cv, e: unknown): string {
  const err = e as Error & {
    status?: number;
    errors?: Record<string, string[]>;
  };

  if (err.status !== 422) {
    return err.message || "Gagal menyimpan CV terjemahan. Coba lagi.";
  }

  const errors = err.errors ?? {};
  const sections = missingSections(errors);

  if (sections.length) {
    return `CV "${cv.title}" belum lengkap, jadi belum bisa disimpan sebagai CV baru. Lengkapi dulu bagian ${sections.join(", ")}.`;
  }

  if (isQuotaError(errors)) {
    return `Batas ${MAX_CV} CV tercapai. Terjemah membuat CV baru, jadi hapus salah satu CV dulu.`;
  }

  return `CV "${cv.title}" belum bisa disimpan sebagai CV baru karena datanya belum lengkap. Buka editor untuk melengkapinya.`;
}

// Alasan paling spesifik disebut dulu karena itu yang paling sering membingungkan.
function translateHint(cv: Cv): string {
  if (cv.is_complete === false) {
    return "Lengkapi dulu CV ini sebelum diterjemahkan";
  }
  if (atLimit.value) {
    return `Batas ${MAX_CV} CV tercapai. Terjemah membuat CV baru, jadi hapus salah satu CV dulu.`;
  }
  return "Buat salinan CV ini dalam bahasa Inggris";
}

/**
 * Duplikat + terjemahkan: server menerjemahkan isi, lalu klien menyimpan
 * hasilnya sebagai CV baru. Dua langkah, dua sumber kegagalan yang dipisah.
 */
async function duplicateTranslate(cv: Cv) {
  // Jaring pengaman untuk balapan state: tombolnya sudah `disabled`, tapi daftar
  // di klien bisa masih menganggap CV ini lengkap padahal server sudah tidak.
  if (cv.is_complete === false) {
    console.warn(
      `[dashboard] Terjemah dilewati: CV ${cv.id} belum lengkap menurut state. Daftar disegarkan.`,
    );
    void cvStore.fetchList();
    return;
  }

  translatingId.value = cv.id;
  cvStore.error = null;
  translatedMessage.value = "";

  try {
    // Gagal terjemah dan gagal simpan punya pesan berbeda, jadi dipisah.
    let data: Awaited<ReturnType<typeof cvApi.translate>>["data"];
    try {
      ({ data } = await cvApi.translate(cv.id));
    } catch (e) {
      cvStore.error = translateError(e);
      return;
    }

    try {
      const created = await cvStore.create({
        title: `${cv.title} (EN)`,
        template: cv.template,
        language: "en",
        data,
      });

      translatedMessage.value = `"${created.title}" sudah dibuat. Membuka editor...`;
      router.push(`/cvs/${created.id}/edit`);
    } catch (e) {
      cvStore.error = createError(cv, e);
    }
  } finally {
    translatingId.value = null;
  }
}

// Simpan blob lewat anchor + object URL (bukan window.open) supaya tahan
// popup-blocker dan tetap tersimpan sebagai attachment.
function saveBlob(blob: Blob) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "";
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 60_000);
}

async function downloadPdf(cv: Cv) {
  if (downloadingPdfId.value !== null) return; // satu render PDF dalam satu waktu

  cvStore.error = null;

  // Tombol sudah disabled untuk CV belum lengkap; ini jaring pengaman kalau
  // dipanggil programatik. Server tetap memvalidasi (sumber kebenaran).
  if (cv.is_complete === false) {
    cvStore.error = "Lengkapi dulu sebelum mengunduh.";
    return;
  }

  downloadingPdfId.value = cv.id;
  const controller = new AbortController();
  pdfAbort = controller;
  // Render Chromium bisa 5-15 dtk; batas ini mencegah tombol berputar selamanya.
  const timer = setTimeout(() => controller.abort(), 60_000);

  try {
    // Cek kelengkapan lewat server DULU tanpa membuka tab: CV belum lengkap -> 422 JSON.
    const res = await fetch(`/api/v1/cvs/${cv.id}/pdf`, {
      credentials: "include",
      headers: { Accept: "application/pdf" },
      signal: controller.signal,
    }).catch(() => null);

    if (!res) {
      cvStore.error = controller.signal.aborted
        ? "PDF lama dibuat. Coba lagi."
        : "Gagal mengunduh PDF. Coba lagi.";
      return;
    }

    if (res.status === 422) {
      const body = (await res.json().catch(() => null)) as {
        message?: string;
      } | null;
      cvStore.error = body?.message ?? "Lengkapi dulu sebelum mengunduh.";
      return;
    }

    if (!res.ok) {
      cvStore.error = "Gagal mengunduh PDF. Coba lagi.";
      return;
    }

    saveBlob(await res.blob());
  } finally {
    clearTimeout(timer);
    if (pdfAbort === controller) pdfAbort = null;
    downloadingPdfId.value = null;
  }
}
</script>

<template>
  <main class="min-h-screen bg-paper dark:bg-background">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
          <div
            class="flex size-12 shrink-0 items-center justify-center rounded-base border-2 border-ink bg-powder text-lg font-black text-ink dark:border-border"
            aria-hidden="true"
          >
            {{ initial }}
          </div>
          <div class="min-w-0">
            <h1
              class="truncate text-xl font-black tracking-tight text-ink sm:text-2xl dark:text-foreground"
            >
              {{ auth.user?.name }}
            </h1>
            <p class="truncate text-sm text-ink/75 dark:text-foreground/70">
              {{ auth.user?.email }}
              <template v-if="memberSince">
                · Bergabung {{ memberSince }}
              </template>
            </p>
          </div>
        </div>

        <RouterLink
          ref="newCvRef"
          to="/cvs/new"
          class="inline-flex h-11 items-center gap-2 rounded-base border-2 border-ink bg-navy px-5 text-sm font-bold text-white shadow-shadow transition-transform hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border dark:focus-visible:outline-foreground"
        >
          Buat CV baru
        </RouterLink>
      </div>

      <dl v-if="total" class="mt-6 grid gap-3 sm:grid-cols-3">
        <div
          class="rounded-base border-2 border-ink bg-white px-4 py-3 shadow-shadow dark:border-border dark:bg-secondary-background"
        >
          <dt
            class="text-xs font-bold uppercase tracking-wide text-ink/75 dark:text-foreground/70"
          >
            Total CV
          </dt>
          <dd
            class="mt-1 text-2xl font-black tabular-nums text-ink dark:text-foreground"
          >
            {{ total }}
          </dd>
        </div>
        <div
          class="rounded-base border-2 border-ink bg-white px-4 py-3 shadow-shadow dark:border-border dark:bg-secondary-background"
        >
          <dt
            class="text-xs font-bold uppercase tracking-wide text-ink/75 dark:text-foreground/70"
          >
            Siap diunduh
          </dt>
          <dd
            class="mt-1 text-2xl font-black tabular-nums text-ink dark:text-foreground"
          >
            {{ readyCount }}
          </dd>
        </div>
        <div
          class="rounded-base border-2 border-ink bg-white px-4 py-3 shadow-shadow dark:border-border dark:bg-secondary-background"
        >
          <dt
            class="text-xs font-bold uppercase tracking-wide text-ink/75 dark:text-foreground/70"
          >
            Batas akun
          </dt>
          <dd
            class="mt-1 text-2xl font-black tabular-nums text-ink dark:text-foreground"
          >
            {{ total
            }}<span class="text-ink/70 dark:text-foreground/50"
              >/{{ MAX_CV }}</span
            >
          </dd>
        </div>
      </dl>

      <div class="mt-8 flex flex-wrap items-baseline justify-between gap-2">
        <h2
          class="text-sm font-black uppercase tracking-wide text-ink dark:text-foreground"
        >
          Daftar CV
        </h2>
        <p
          v-if="atLimit"
          class="text-xs font-medium text-red-700 dark:text-red-300"
        >
          Batas {{ MAX_CV }} CV tercapai. Hapus salah satu untuk membuat yang
          baru.
        </p>
      </div>

      <p
        v-if="cvStore.error"
        role="alert"
        class="mt-4 rounded-base border-2 border-red-600 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:border-red-300 dark:bg-red-950/40 dark:text-red-300"
      >
        {{ cvStore.error }}
      </p>

      <p
        v-if="translatedMessage"
        role="status"
        aria-live="polite"
        class="mt-4 rounded-base border-2 border-ink bg-powder px-3 py-2 text-sm font-semibold text-ink dark:border-border"
      >
        {{ translatedMessage }}
      </p>

      <!-- Skeleton meniru bentuk kartu supaya tata letak tidak melompat. -->
      <div
        v-if="cvStore.loading && !cvStore.list.length"
        class="mt-4 flex flex-col gap-3"
        aria-busy="true"
        aria-live="polite"
      >
        <span class="sr-only">Memuat daftar CV</span>
        <div
          v-for="n in 3"
          :key="n"
          class="flex gap-4 rounded-base border-2 border-ink/25 bg-white/60 p-4 dark:border-border/40 dark:bg-secondary-background/50"
        >
          <div
            class="h-[112px] w-[79px] shrink-0 animate-pulse rounded-[2px] bg-ink/10 dark:bg-foreground/15"
          />
          <div class="flex-1 space-y-3 py-1">
            <div
              class="h-4 w-1/3 animate-pulse rounded bg-ink/10 dark:bg-foreground/15"
            />
            <div
              class="h-3 w-1/4 animate-pulse rounded bg-ink/10 dark:bg-foreground/15"
            />
            <div
              class="h-9 w-2/3 animate-pulse rounded-base bg-ink/10 dark:bg-foreground/15"
            />
          </div>
        </div>
      </div>

      <div
        v-else-if="!cvStore.list.length"
        class="mt-4 rounded-base border-2 border-ink bg-white px-6 py-12 shadow-shadow sm:px-10 dark:border-border dark:bg-secondary-background"
      >
        <div class="mx-auto max-w-md">
          <div
            class="flex size-12 items-center justify-center rounded-base border-2 border-ink bg-paper dark:border-border dark:bg-background"
            aria-hidden="true"
          >
            <FileText class="size-6 text-ink dark:text-foreground" />
          </div>
          <h3 class="mt-4 text-lg font-black text-ink dark:text-foreground">
            Belum ada CV di akun ini
          </h3>
          <p
            class="mt-1 text-sm leading-relaxed text-ink/80 dark:text-foreground/70"
          >
            Semua CV yang Anda buat tersimpan di daftar ini, lengkap dengan
            template dan tanggal perubahan terakhir.
          </p>

          <ol class="mt-6 flex flex-col gap-3">
            <li class="flex gap-3">
              <span
                class="flex size-6 shrink-0 items-center justify-center rounded-base border-2 border-ink text-xs font-black text-ink dark:border-border dark:text-foreground"
                aria-hidden="true"
                >1</span
              >
              <p class="text-sm text-ink/80 dark:text-foreground/80">
                Isi sembilan langkah form, dari data pribadi sampai keahlian.
              </p>
            </li>
            <li class="flex gap-3">
              <span
                class="flex size-6 shrink-0 items-center justify-center rounded-base border-2 border-ink text-xs font-black text-ink dark:border-border dark:text-foreground"
                aria-hidden="true"
                >2</span
              >
              <p class="text-sm text-ink/80 dark:text-foreground/80">
                Pilih template Modern, Classic, atau Neon sambil melihat
                preview.
              </p>
            </li>
            <li class="flex gap-3">
              <span
                class="flex size-6 shrink-0 items-center justify-center rounded-base border-2 border-ink text-xs font-black text-ink dark:border-border dark:text-foreground"
                aria-hidden="true"
                >3</span
              >
              <p class="text-sm text-ink/80 dark:text-foreground/80">
                Unduh PDF A4 yang siap dibaca sistem ATS.
              </p>
            </li>
          </ol>

          <RouterLink
            to="/cvs/new"
            class="mt-6 inline-flex h-11 items-center gap-2 rounded-base border-2 border-ink bg-navy px-5 text-sm font-bold text-white shadow-shadow transition-transform hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border dark:focus-visible:outline-foreground"
          >
            Buat CV pertama
          </RouterLink>
        </div>
      </div>

      <ul
        v-else
        ref="listRef"
        class="mt-4 flex flex-col gap-3"
        :aria-busy="translating"
      >
        <li
          v-for="cv in cvStore.list"
          :key="cv.id"
          class="rounded-base border-2 border-ink bg-white p-3 shadow-shadow transition-shadow hover:shadow-[6px_6px_0_0_var(--border)] sm:p-4 dark:border-border dark:bg-secondary-background"
        >
          <div class="flex min-w-0 gap-4">
            <!-- Bingkai ukuran tetap: thumbnail di dalamnya di-scale 0.348 -->
            <div class="h-[112px] w-[79px] shrink-0">
              <CvThumb :title="cv.title" :template="cv.template" />
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
              <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <h3
                  class="line-clamp-1 min-w-0 text-base font-black text-ink dark:text-foreground"
                  :title="cv.title"
                >
                  {{ cv.title }}
                </h3>
                <span
                  class="shrink-0 rounded-base border-2 border-ink px-1.5 text-[11px] font-bold leading-5 dark:border-border"
                  :class="TEMPLATES[cv.template].badgeClass"
                >
                  {{ TEMPLATES[cv.template].label }}
                </span>
                <span
                  v-if="cv.is_complete === false"
                  class="shrink-0 rounded-base border-2 border-ink px-1.5 text-[11px] font-bold leading-5 text-ink dark:border-border dark:text-foreground"
                >
                  Belum lengkap
                </span>
              </div>

              <p
                class="mt-1.5 truncate text-xs font-medium text-ink/75 dark:text-foreground/70"
              >
                {{ cv.language === "id" ? "Bahasa Indonesia" : "English" }} ·
                Diubah {{ fmtDate(cv.updated_at) }}
              </p>

              <!-- Aksi: satu tombol utama navy, sisanya ikon agar baris tidak pecah.
                   mt-auto menempelkan baris aksi ke dasar kartu apa pun panjang isinya. -->
              <div
                class="mt-auto flex min-w-0 flex-wrap items-center gap-2 pt-3"
              >
                <RouterLink
                  :to="`/cvs/${cv.id}/edit`"
                  class="inline-flex h-9 items-center gap-1.5 rounded-base border-2 border-ink bg-navy px-3.5 text-xs font-bold text-white shadow-shadow transition-transform hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border dark:focus-visible:outline-foreground"
                >
                  <Pencil class="size-4" aria-hidden="true" />
                  Edit
                </RouterLink>

                <button
                  type="button"
                  :disabled="
                    cv.is_complete === false || downloadingPdfId !== null
                  "
                  :aria-busy="downloadingPdfId === cv.id"
                  :title="
                    cv.is_complete === false
                      ? 'Lengkapi dulu sebelum mengunduh PDF'
                      : downloadingPdfId === cv.id
                        ? 'Menyiapkan PDF...'
                        : 'Unduh PDF'
                  "
                  :aria-label="
                    downloadingPdfId === cv.id ? 'Menyiapkan PDF' : 'Unduh PDF'
                  "
                  @click="downloadPdf(cv)"
                  class="flex size-9 items-center justify-center rounded-base border-2 border-ink bg-white text-ink transition-colors hover:bg-powder focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:focus-visible:outline-foreground disabled:cursor-not-allowed disabled:border-ink/30 disabled:bg-transparent disabled:text-ink/40 disabled:hover:bg-transparent dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-white/15 dark:disabled:border-border/40 dark:disabled:text-foreground/40"
                >
                  <Loader2
                    v-if="downloadingPdfId === cv.id"
                    class="size-4 animate-spin"
                    aria-hidden="true"
                  />
                  <Download v-else class="size-4" aria-hidden="true" />
                </button>

                <!-- Teks selalu terlihat: aksi ini mengonsumsi antrean terjemahan
                     dan aplikasi ini dwibahasa, jadi ikon saja tidak cukup jelas. -->
                <button
                  v-if="cv.language === 'id'"
                  type="button"
                  :disabled="
                    translatingId === cv.id ||
                    atLimit ||
                    cv.is_complete === false
                  "
                  :title="translateHint(cv)"
                  @click="duplicateTranslate(cv)"
                  class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-base border-2 border-ink bg-white px-3 text-xs font-bold text-ink transition-colors hover:bg-powder focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:focus-visible:outline-foreground disabled:cursor-not-allowed disabled:border-ink/45 disabled:bg-transparent disabled:text-ink/80 disabled:hover:bg-transparent dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-white/15 dark:disabled:border-border/60 dark:disabled:text-foreground/75"
                >
                  <Loader2
                    v-if="translatingId === cv.id"
                    class="size-4 animate-spin"
                    aria-hidden="true"
                  />
                  <Languages v-else class="size-4" aria-hidden="true" />
                  {{
                    translatingId === cv.id ? "Menerjemahkan..." : "Terjemah EN"
                  }}
                </button>

                <button
                  type="button"
                  title="Hapus CV"
                  data-cv-delete
                  :aria-label="`Hapus CV ${cv.title}`"
                  @click="askDelete(cv)"
                  class="flex size-9 items-center justify-center rounded-base border-2 border-ink bg-white text-red-700 transition-colors hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border dark:bg-background dark:text-red-300 dark:hover:bg-red-950/40 dark:focus-visible:outline-foreground"
                >
                  <Trash2 class="size-4" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <ConfirmDialog
      :open="pendingDelete !== null"
      title="Hapus CV ini?"
      :description="
        pendingDelete
          ? `&quot;${pendingDelete.title}&quot; akan dihapus permanen dari akun Anda. Tindakan ini tidak bisa dibatalkan.`
          : ''
      "
      confirm-label="Hapus CV"
      :pending="deleting"
      pending-label="Menghapus..."
      @update:open="!$event && cancelDelete()"
      @cancel="cancelDelete"
      @confirm="confirmDelete"
    />
  </main>
</template>
