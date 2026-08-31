<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useCvStore } from "@/stores/cv";
import { emptyCvData } from "@/types/cv";
import type { CvData } from "@/types/cv";
import CvForm from "@/components/cv/CvForm.vue";
import CvPreview from "@/components/cv/CvPreview.vue";

const props = defineProps<{ id?: string }>();
const router = useRouter();
const cvStore = useCvStore();
const win = window;

const isEdit = computed(() => !!cvId.value);
const cvId = ref<number | undefined>(props.id ? Number(props.id) : undefined);
const title = ref("");
const template = ref("modern");
const language = ref("id");
const data = ref<CvData>(emptyCvData());
const error = ref<string | null>(null);
const saving = ref(false);
const drafting = ref(false);
const toast = ref<{ msg: string; ok: boolean } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;

function showToast(msg: string, ok = true) {
  toast.value = { msg, ok };
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toast.value = null), 2600);
}

onMounted(async () => {
  if (isEdit.value && cvId.value) {
    await cvStore.fetchOne(cvId.value);
    if (cvStore.current) {
      title.value = cvStore.current.title;
      template.value = cvStore.current.template;
      language.value = cvStore.current.language;
      const d = cvStore.current.data ?? emptyCvData();
      if (
        typeof (d as unknown as Record<string, unknown>).projects === "string"
      ) {
        const s = (
          (d as unknown as Record<string, unknown>).projects as string
        ).trim();
        d.projects = s
          ? [{ title: s, role: "", objective: "", techStack: "" }]
          : [];
      }
      data.value = d;
    }
  }
});

async function submit() {
  error.value = null;
  saving.value = true;
  try {
    const payload = {
      title: title.value,
      template: template.value,
      language: language.value,
      data: data.value,
    };
    if (cvId.value) await cvStore.update(cvId.value, payload);
    else await cvStore.create(payload);
    router.push("/dashboard");
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Gagal menyimpan CV";
  } finally {
    saving.value = false;
  }
}

async function draftSave() {
  error.value = null;
  drafting.value = true;
  try {
    const payload = {
      title: title.value,
      template: template.value,
      language: language.value,
      data: data.value,
    };
    if (cvId.value) {
      await cvStore.update(cvId.value, payload);
    } else {
      const cv = await cvStore.create(payload);
      cvId.value = cv.id;
    }
    showToast("Draft tersimpan");
  } catch (e) {
    showToast(e instanceof Error ? e.message : "Gagal menyimpan draft", false);
  } finally {
    drafting.value = false;
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-50 dark:bg-background">
    <div class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6">
      <!-- Sticky header: judul + aksi selalu terlihat saat scroll -->
      <div
        class="sticky top-0 z-20 mb-4 flex items-center gap-3 rounded-xl border border-slate-200 bg-white/95 px-4 py-2.5 shadow-sm backdrop-blur dark:border-border dark:bg-secondary-background/95"
      >
        <button
          @click="router.push('/dashboard')"
          class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        >
          ← Kembali
        </button>
        <h1 class="text-xl font-bold text-slate-900 dark:text-foreground">
          {{ isEdit ? "Edit CV" : "Buat CV Baru" }}
        </h1>
        <div class="ml-auto flex items-center gap-3">
          <span
            class="hidden text-xs text-slate-400 dark:text-foreground/60 sm:inline"
          >
            Preview update otomatis saat mengetik</span
          >
          <button
            @click="draftSave"
            :disabled="drafting"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
          >
            {{ drafting ? "Menyimpan..." : "Simpan Draft" }}
          </button>
          <button
            v-if="isEdit"
            @click="win.open(`/api/v1/cvs/${cvId}/pdf`, '_blank')"
            class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 dark:bg-main dark:hover:bg-blue-700"
          >
            Download PDF
          </button>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-[480px_1fr] xl:grid-cols-[520px_1fr]">
        <!-- Form -->
        <div
          class="rounded-2xl bg-white p-5 shadow-sm sm:p-6 lg:max-h-[calc(100vh-6rem)] lg:overflow-auto dark:bg-secondary-background"
        >
          <p
            v-if="error"
            class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400"
          >
            {{ error }}
          </p>
          <CvForm
            v-model="data"
            v-model:title="title"
            v-model:template="template"
            v-model:language="language"
            :cv-id="cvId"
            @submit="submit"
          />
          <p
            v-if="saving"
            class="mt-3 text-center text-xs text-slate-400 dark:text-foreground/60"
          >
            Menyimpan...
          </p>
        </div>

        <!-- Preview -->
        <div
          class="lg:sticky lg:top-6 lg:max-h-[calc(100vh-6rem)] lg:overflow-auto"
        >
          <div class="mb-2 flex items-center justify-between">
            <span
              class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-foreground/70"
              >Preview · {{ template }}</span
            >
          </div>
          <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-border dark:bg-secondary-background"
          >
            <CvPreview :data="data" :template="template" />
          </div>
        </div>
      </div>
    </div>

    <!-- Toast draft tersimpan -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-1"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="toast"
        class="fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-lg"
        :class="
          toast.ok
            ? 'bg-slate-900 dark:bg-secondary-background dark:text-foreground'
            : 'bg-red-600 dark:bg-red-900'
        "
      >
        {{ toast.msg }}
      </div>
    </transition>
  </main>
</template>
