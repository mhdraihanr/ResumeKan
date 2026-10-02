<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
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

const auth = useAuthStore();
const cvStore = useCvStore();
const router = useRouter();

onMounted(() => cvStore.fetchList());

/** Batas jumlah CV per akun, dipakai sebagai penanda kapasitas di ringkasan. */
const MAX_CV = 10;

const total = computed(() => cvStore.list.length);
/** Dihitung dari is_complete yang datang dari server, bukan angka karangan. */
const readyCount = computed(
  () => cvStore.list.filter((cv) => cv.is_complete !== false).length,
);
const atLimit = computed(() => total.value >= MAX_CV);

/** Aksi terjemah sedang berjalan: dipakai untuk aria-busy di daftar. */
const translating = ref(false);

const initial = computed(() =>
  (auth.user?.name ?? "?").trim().charAt(0).toUpperCase(),
);

const memberSince = computed(() => fmtDate(auth.user?.created_at));

const TEMPLATE_LABEL: Record<Cv["template"], string> = {
  modern: "Modern",
  classic: "Classic",
  neon: "Neon",
};

const templateBadgeClass: Record<Cv["template"], string> = {
  modern: "bg-navy text-white",
  classic: "bg-ink text-white dark:bg-foreground dark:text-ink",
  neon: "bg-powder text-ink",
};

async function handleDelete(cv: Cv) {
  if (!confirm(`Hapus CV "${cv.title}"? Tindakan ini tidak bisa dibatalkan.`)) {
    return;
  }
  try {
    await cvStore.remove(cv.id);
  } catch (e) {
    cvStore.error = e instanceof Error ? e.message : "Gagal menghapus CV";
  }
}

const translatingId = ref<number | null>(null);
/** Id CV yang PDF-nya sedang dirender; dipakai untuk spinner tombol unduh. */
const downloadingPdfId = ref<number | null>(null);
let pdfAbort: AbortController | null = null;

// Batalkan permintaan PDF yang masih berjalan saat pengguna meninggalkan halaman,
// supaya tidak ada render sia-sia dan state tidak menggantung.
onBeforeUnmount(() => pdfAbort?.abort());
const translatedMessage = ref("");

const SECTION_LABEL: Record<string, string> = {
  experiences: "Pengalaman",
  education: "Pendidikan",
  organizations: "Organisasi",
  certificates: "Sertifikat",
  projects: "Proyek",
};

/**
 * Pesan gagal `/translate`. Endpoint ini tidak memvalidasi `data.*`, jadi
 * satu-satunya 422 di sini adalah bentuk yang tidak terduga; kegagalan yang
 * benar-benar mungkin adalah 502 layanan dan 429 throttle.
 */
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

/**
 * Pesan gagal simpan CV terjemahan.
 *
 * Ini bagian yang benar-benar bisa mengembalikan 422. Validasi server memakai
 * pesan Laravel mentah berbahasa Inggris (mis. "The data.certificates.0.issuer
 * field is required..."), dan pesan itu tidak pernah ditampilkan apa adanya:
 * kita baca kunci error untuk menyebut bagian mana yang harus dilengkapi.
 */
function createError(cv: Cv, e: unknown): string {
  const err = e as Error & {
    status?: number;
    errors?: Record<string, string[]>;
  };

  if (err.status === 422) {
    const sections = new Set<string>();

    for (const key of Object.keys(err.errors ?? {})) {
      const m = key.match(
        /^data\.(experiences|education|organizations|certificates|projects)\./,
      );
      if (m) sections.add(SECTION_LABEL[m[1]!]!);
    }

    if (sections.size) {
      return `CV "${cv.title}" belum lengkap, jadi belum bisa disimpan sebagai CV baru. Lengkapi dulu bagian ${[
        ...sections,
      ].join(", ")}.`;
    }

    // 422 juga dipakai server saat kuota penuh (`errors.title`), bukan hanya saat
    // data belum lengkap. Tanpa cabang ini pengguna diberi tahu CV-nya "belum
    // lengkap" padahal masalahnya jumlah CV. Kuota bisa lolos dari `atLimit`
    // karena daftar di state bisa basi (mis. CV ditambah dari tab lain).
    const quota = Object.values(err.errors ?? {})
      .flat()
      .some((m) => /maksimal\s+\d+\s+cv/i.test(m));

    if (quota) {
      return `Batas ${MAX_CV} CV tercapai. Terjemah membuat CV baru, jadi hapus salah satu CV dulu.`;
    }

    return `CV "${cv.title}" belum bisa disimpan sebagai CV baru karena datanya belum lengkap. Buka editor untuk melengkapinya.`;
  }

  return err.message || "Gagal menyimpan CV terjemahan. Coba lagi.";
}

/** Alasan tombol terjemah nonaktif, dipakai untuk `title`.
 *  Urutannya sengaja: alasan paling spesifik (data belum lengkap) disebut dulu,
 *  karena itu yang paling sering bikin pengguna bingung.
 */
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
 * Duplikat + terjemahkan. Ini satu rangkaian: server menerjemahkan isi, lalu
 * klien menyimpan hasilnya sebagai CV baru. Bukan aksi instan, jadi tombolnya
 * memakai state pending dan teksnya berubah, bukan sekadar tooltip.
 */
async function duplicateTranslate(cv: Cv) {
  // Jaring pengaman, bukan jalur pesan pengguna. Tombolnya sudah `disabled`,
  // jadi manusia tidak bisa sampai ke sini. Guard ini hanya menangkap kasus
  // balapan: daftar di state masih menganggap CV lengkap padahal data server
  // sudah tidak. Daripada mengirim payload yang pasti ditolak 422, kita diam
  // dan minta daftar disegarkan.
  if (cv.is_complete === false) {
    console.warn(
      `[dashboard] Terjemah dilewati: CV ${cv.id} belum lengkap menurut state. Daftar disegarkan.`,
    );
    void cvStore.fetchList();
    return;
  }

  translatingId.value = cv.id;
  translating.value = true;
  cvStore.error = null;
  translatedMessage.value = "";

  // Dua langkah, dua sumber kegagalan yang berbeda. Dipisah supaya pesannya
  // tidak saling tertukar: gagal terjemah bukan gagal simpan, dan sebaliknya.
  let data: Awaited<ReturnType<typeof cvApi.translate>>["data"];
  try {
    ({ data } = await cvApi.translate(cv.id));
  } catch (e) {
    cvStore.error = translateError(e);
    translatingId.value = null;
    translating.value = false;
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
  } finally {
    translatingId.value = null;
    translating.value = false;
  }
}

async function downloadPdf(cv: Cv) {
  if (downloadingPdfId.value !== null) return; // satu render PDF dalam satu waktu

  cvStore.error = "";

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
    // Cek kelengkapan lewat server DULU tanpa membuka tab: CV belum lengkap ->
    // 422 JSON yang akan tampil jelek bila dibuka langsung sebagai tab.
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
      const body = await res.json().catch(() => null);
      cvStore.error = body?.message ?? "Lengkapi dulu sebelum mengunduh.";
      return;
    }

    if (!res.ok) {
      cvStore.error = "Gagal mengunduh PDF. Coba lagi.";
      return;
    }

    // Sukses: unduh lewat anchor + object URL (bukan window.open) supaya tahan
    // popup-blocker dan tetap tersimpan sebagai attachment.
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "";
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 60_000);
  } finally {
    clearTimeout(timer);
    if (pdfAbort === controller) pdfAbort = null;
    downloadingPdfId.value = null;
  }
}

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
</script>

<template>
  <main class="min-h-screen bg-paper dark:bg-background">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-10">
      <!-- Identitas: baris yang isinya data user sendiri, bukan dekorasi -->
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
          to="/cvs/new"
          class="inline-flex h-11 items-center gap-2 rounded-base border-2 border-ink bg-navy px-5 text-sm font-bold text-white shadow-shadow transition-transform hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border"
        >
          Buat CV baru
        </RouterLink>
      </div>

      <!-- Kapasitas: total CV, siap diunduh, batas akun (semuanya angka nyata) -->
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

      <!-- Error -->
      <p
        v-if="cvStore.error"
        role="alert"
        class="mt-4 rounded-base border-2 border-red-600 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:border-red-300 dark:bg-red-950/40 dark:text-red-300"
      >
        {{ cvStore.error }}
      </p>

      <!-- Sukses terjemah: hanya terlihat sekejap sebelum router berpindah -->
      <p
        v-if="translatedMessage"
        role="status"
        aria-live="polite"
        class="mt-4 rounded-base border-2 border-ink bg-powder px-3 py-2 text-sm font-semibold text-ink dark:border-border"
      >
        {{ translatedMessage }}
      </p>

      <!-- Loading: skeleton menyamai bentuk kartu supaya tata letak tidak lompat -->
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

      <!-- Kosong: sebut apa yang bisa dibuat, lalu tiga langkah nyata produk -->
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
            class="mt-6 inline-flex h-11 items-center gap-2 rounded-base border-2 border-ink bg-navy px-5 text-sm font-bold text-white shadow-shadow transition-transform hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border"
          >
            Buat CV pertama
          </RouterLink>
        </div>
      </div>

      <!-- Daftar: satu kartu per CV -->
      <ul v-else class="mt-4 flex flex-col gap-3" :aria-busy="translating">
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
              <!-- Judul + tag template dalam satu baris supaya kartu tetap pendek -->
              <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <h3
                  class="line-clamp-1 min-w-0 text-base font-black text-ink dark:text-foreground"
                  :title="cv.title"
                >
                  {{ cv.title }}
                </h3>
                <span
                  class="shrink-0 rounded-base border-2 border-ink px-1.5 text-[11px] font-bold leading-5 dark:border-border"
                  :class="templateBadgeClass[cv.template]"
                >
                  {{ TEMPLATE_LABEL[cv.template] }}
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
                  class="inline-flex h-9 items-center gap-1.5 rounded-base border-2 border-ink bg-navy px-3.5 text-xs font-bold text-white shadow-shadow transition-transform hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border"
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
                  class="flex size-9 items-center justify-center rounded-base border-2 border-ink bg-white text-ink transition-colors hover:bg-powder focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink disabled:cursor-not-allowed disabled:border-ink/30 disabled:bg-transparent disabled:text-ink/40 disabled:hover:bg-transparent dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-white/15 dark:disabled:border-border/40 dark:disabled:text-foreground/40"
                >
                  <Loader2
                    v-if="downloadingPdfId === cv.id"
                    class="size-4 animate-spin"
                    aria-hidden="true"
                  />
                  <Download v-else class="size-4" aria-hidden="true" />
                </button>

                <!-- Terjemah + duplikat dalam satu aksi, makna ikonnya tunggal.
                     Teks selalu terlihat karena aksi ini mirip "Hapus" (risiko:
                     antrean terjemahan terpakai) dan app ini dwibahasa. -->
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
                  class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-base border-2 border-ink bg-white px-3 text-xs font-bold text-ink transition-colors hover:bg-powder focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink disabled:cursor-not-allowed disabled:border-ink/45 disabled:bg-transparent disabled:text-ink/80 disabled:hover:bg-transparent dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-white/15 dark:disabled:border-border/60 dark:disabled:text-foreground/75"
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
                  v-if="cv.language !== 'id'"
                  type="button"
                  title="Hapus CV"
                  aria-label="Hapus CV"
                  @click="handleDelete(cv)"
                  class="flex size-9 items-center justify-center rounded-base border-2 border-ink bg-white text-red-700 transition-colors hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border dark:bg-background dark:text-red-300 dark:hover:bg-red-950/40"
                >
                  <Trash2 class="size-4" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </main>
</template>
