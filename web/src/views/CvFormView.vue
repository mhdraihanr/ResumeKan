<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useCvStore } from "@/stores/cv";
import { emptyCvData } from "@/types/cv";
import type { CvData } from "@/types/cv";
import CvForm from "@/components/cv/CvForm.vue";

const props = defineProps<{ id?: string }>();
const router = useRouter();
const cvStore = useCvStore();

const isEdit = !!props.id;
const title = ref("");
const template = ref("modern");
const language = ref("id");
const data = ref<CvData>(emptyCvData());
const error = ref<string | null>(null);
const saving = ref(false);

onMounted(async () => {
  if (isEdit) {
    await cvStore.fetchOne(Number(props.id));
    if (cvStore.current) {
      title.value = cvStore.current.title;
      template.value = cvStore.current.template;
      language.value = cvStore.current.language;
      const d = cvStore.current.data ?? emptyCvData();
      // Backward compat: projects string lama → array
      if (
        typeof (d as unknown as Record<string, unknown>).projects === "string"
      ) {
        const s = (
          (d as unknown as Record<string, unknown>).projects as string
        ).trim();
        d.projects = s
          ? [{ title: s, role: "—", objective: "", techStack: "" }]
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
    if (isEdit) await cvStore.update(Number(props.id), payload);
    else await cvStore.create(payload);
    router.push("/dashboard");
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Gagal menyimpan CV";
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
      <div class="mb-6 flex items-center gap-3">
        <button
          @click="router.push('/dashboard')"
          class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          ← Kembali
        </button>
        <h1 class="text-xl font-bold text-slate-900">
          {{ isEdit ? "Edit CV" : "Buat CV Baru" }}
        </h1>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
        <p
          v-if="error"
          class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {{ error }}
        </p>
        <CvForm
          v-model="data"
          v-model:title="title"
          v-model:template="template"
          v-model:language="language"
          @submit="submit"
        />
        <p v-if="saving" class="mt-3 text-center text-xs text-slate-400">
          Menyimpan...
        </p>
      </div>
    </div>
  </main>
</template>
