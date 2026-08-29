<script setup lang="ts">
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useCvStore } from "@/stores/cv";

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
  <main class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      <!-- Header -->
      <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            CV Saya
          </h1>
          <p class="mt-1 text-sm text-slate-500">
            Halo,
            <span class="font-semibold text-slate-900">{{
              auth.user?.name
            }}</span>
            · {{ auth.user?.email }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="router.push('/cvs/new')"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
          >
            + Buat CV
          </button>
          <button
            @click="handleLogout"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Logout
          </button>
        </div>
      </div>

      <!-- Error -->
      <p
        v-if="cvStore.error"
        class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"
      >
        {{ cvStore.error }}
      </p>

      <!-- Loading -->
      <p
        v-if="cvStore.loading && !cvStore.list.length"
        class="py-12 text-center text-sm text-slate-400"
      >
        Memuat...
      </p>

      <!-- Empty -->
      <div
        v-else-if="!cvStore.list.length"
        class="rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-16 text-center"
      >
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-xl"
        >
          📄
        </div>
        <h2 class="mt-4 text-sm font-semibold text-slate-900">Belum ada CV</h2>
        <p class="mx-auto mt-1 max-w-sm text-sm leading-relaxed text-slate-500">
          Buat CV pertama Anda, isi form terstruktur, pilih template, dan siap
          untuk ATS.
        </p>
        <button
          @click="router.push('/cvs/new')"
          class="mt-6 rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
        >
          Buat CV pertama
        </button>
      </div>

      <!-- Grid -->
      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="cv in cvStore.list"
          :key="cv.id"
          class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
        >
          <div class="flex items-start justify-between gap-2">
            <h2
              class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900"
            >
              {{ cv.title }}
            </h2>
            <span
              class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
              >{{ cv.template }}</span
            >
          </div>
          <p class="mt-1 text-xs text-slate-400">
            {{ cv.language === "id" ? "Indonesia" : "English" }} ·
            {{ fmtDate(cv.updated_at) }}
          </p>
          <div class="mt-4 flex gap-2">
            <button
              @click="router.push(`/cvs/${cv.id}/edit`)"
              class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
            >
              Edit
            </button>
            <button
              @click="downloadPdf(cv.id)"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
            >
              PDF
            </button>
            <button
              @click="handleDelete(cv.id)"
              class="rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
            >
              Hapus
            </button>
          </div>
        </article>
      </div>

      <p
        v-if="cvStore.list.length >= 10"
        class="mt-4 text-center text-xs text-amber-600"
      >
        Batas 10 CV tercapai.
      </p>
    </div>
  </main>
</template>
