<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useCvStore } from "@/stores/cv";
import { cvApi } from "@/api/cv";
import type { Cv } from "@/types/cv";

const auth = useAuthStore();
const cvStore = useCvStore();
const router = useRouter();

onMounted(() => cvStore.fetchList());

async function handleLogout() {
  await auth.logout();
  router.push("/login");
}

async function handleDelete(id: number) {
  if (!confirm("Hapus CV ini?")) return;
  await cvStore.remove(id);
}

const translatingId = ref<number | null>(null);

async function duplicateTranslate(cv: Cv) {
  translatingId.value = cv.id;
  try {
    const { data } = await cvApi.translate(cv.id);
    const created = await cvStore.create({
      title: `${cv.title} (EN)`,
      template: cv.template,
      language: "en",
      data,
    });
    router.push(`/cvs/${created.id}/edit`);
  } catch (e) {
    cvStore.error = (e as Error).message;
  } finally {
    translatingId.value = null;
  }
}

function downloadPdf(id: number) {
  window.open(`/api/v1/cvs/${id}/pdf`, "_blank");
}

function fmtDate(s: string) {
  try {
    return new Date(s).toLocaleDateString("id-ID", {
      day: "numeric",
      month: "short",
      year: "numeric",
    });
  } catch {
    return s;
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-50 dark:bg-background">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      <!-- Header -->
      <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-slate-900 dark:text-foreground"
          >
            CV Saya
          </h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-foreground/70">
            Halo,
            <span class="font-semibold text-slate-900 dark:text-foreground">{{
              auth.user?.name
            }}</span>
            · {{ auth.user?.email }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="router.push('/cvs/new')"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-main"
          >
            + Buat CV
          </button>
          <button
            @click="handleLogout"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
          >
            Logout
          </button>
        </div>
      </div>

      <!-- Error -->
      <p
        v-if="cvStore.error"
        class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400"
      >
        {{ cvStore.error }}
      </p>

      <!-- Loading -->
      <p
        v-if="cvStore.loading && !cvStore.list.length"
        class="py-12 text-center text-sm text-slate-400 dark:text-foreground/50"
      >
        Memuat...
      </p>

      <!-- Empty -->
      <div
        v-else-if="!cvStore.list.length"
        class="rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-16 text-center dark:border-border dark:bg-secondary-background"
      >
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-xl dark:bg-ink/20"
        >
          📄
        </div>
        <h2
          class="mt-4 text-sm font-semibold text-slate-900 dark:text-foreground"
        >
          Belum ada CV
        </h2>
        <p
          class="mx-auto mt-1 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-foreground/70"
        >
          Buat CV pertama Anda, isi form terstruktur, pilih template, dan siap
          untuk ATS.
        </p>
        <button
          @click="router.push('/cvs/new')"
          class="mt-6 rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-main"
        >
          Buat CV pertama
        </button>
      </div>

      <!-- Grid -->
      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="cv in cvStore.list"
          :key="cv.id"
          class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-border dark:bg-secondary-background"
        >
          <div class="flex items-start justify-between gap-2">
            <h2
              class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 dark:text-foreground"
            >
              {{ cv.title }}
            </h2>
            <span
              class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-ink/20 dark:text-foreground/70"
              >{{ cv.template }}</span
            >
          </div>
          <p class="mt-1 text-xs text-slate-400 dark:text-foreground/50">
            {{ cv.language === "id" ? "Indonesia" : "English" }} ·
            {{ fmtDate(cv.updated_at) }}
          </p>
          <div class="mt-4 flex gap-2">
            <button
              @click="router.push(`/cvs/${cv.id}/edit`)"
              class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
            >
              Edit
            </button>
            <button
              @click="downloadPdf(cv.id)"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
            >
              PDF
            </button>
            <button
              v-if="cv.language === 'id'"
              :disabled="translatingId === cv.id"
              @click="duplicateTranslate(cv)"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-50 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
            >
              {{ translatingId === cv.id ? "Menerjemahkan..." : "Duplikat & terjemahkan EN" }}
            </button>
            <button
              @click="handleDelete(cv.id)"
              class="rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400"
            >
              Hapus
            </button>
          </div>
        </article>
      </div>

      <p
        v-if="cvStore.list.length >= 10"
        class="mt-4 text-center text-xs text-amber-600 dark:text-amber-400"
      >
        Batas 10 CV tercapai.
      </p>
    </div>
  </main>
</template>
