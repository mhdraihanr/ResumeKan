<script setup lang="ts">
import { ref } from "vue";
import type { CvData } from "@/types/cv";
import { cvApi } from "@/api/cv";
import MetaStep from "./steps/MetaStep.vue";
import PersonalStep from "./steps/PersonalStep.vue";
import SummaryStep from "./steps/SummaryStep.vue";
import ExperienceStep from "./steps/ExperienceStep.vue";
import EducationStep from "./steps/EducationStep.vue";
import OrganizationStep from "./steps/OrganizationStep.vue";
import SkillsStep from "./steps/SkillsStep.vue";
import ProjectsStep from "./steps/ProjectsStep.vue";
import CertificatesStep from "./steps/CertificatesStep.vue";
import OtherStep from "./steps/OtherStep.vue";

const props = defineProps<{
  title: string;
  template: string;
  language: string;
  cvId?: number;
}>();
const emit = defineEmits<{
  "update:title": [v: string];
  "update:template": [v: string];
  "update:language": [v: string];
  submit: [];
}>();

const local = defineModel<CvData>("modelValue", { required: true });

const aiLoading = ref(false);
const aiError = ref("");

const activeStep = ref(0);
const steps = [
  { label: "Info", key: "meta" },
  { label: "Pribadi", key: "personal" },
  { label: "Ringkasan", key: "summary" },
  { label: "Pengalaman", key: "experience" },
  { label: "Pendidikan", key: "education" },
  { label: "Organisasi", key: "organization" },
  { label: "Keahlian", key: "skills" },
  { label: "Proyek", key: "projects" },
  { label: "Sertifikat", key: "certificates" },
  { label: "Lainnya", key: "other" },
];

async function generateSummary() {
  if (!props.cvId) {
    aiError.value = "Simpan CV dulu sebelum generate.";
    return;
  }
  aiLoading.value = true;
  aiError.value = "";
  try {
    const res = await cvApi.aiSummary(props.cvId, props.language, local.value);
    local.value.summary = res.summary;
  } catch (e: unknown) {
    const err = e as { status?: number; message?: string };
    if (err.status === 429)
      aiError.value = "Terlalu sering, coba lagi 1 menit.";
    else if (err.status === 502 || err.status === 503)
      aiError.value = err.message || "AI tidak tersedia.";
    else aiError.value = err.message || "Gagal generate.";
  } finally {
    aiLoading.value = false;
  }
}
</script>

<template>
  <form class="cv-form space-y-6" @submit.prevent="emit('submit')">
    <!-- Stepper nav -->
    <nav
      class="flex flex-wrap gap-1.5 border-b border-slate-200 pb-3 dark:border-border"
    >
      <button
        v-for="(step, i) in steps"
        :key="step.key"
        type="button"
        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition"
        :class="
          i === activeStep
            ? 'bg-slate-900 text-white dark:bg-main'
            : i < activeStep
              ? 'text-slate-700 hover:bg-slate-200 dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground'
              : 'text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:text-foreground/60 dark:hover:bg-white/15 dark:hover:text-foreground'
        "
        @click="activeStep = i"
      >
        <span
          class="size-4 rounded-full text-center text-[10px] leading-4"
          :class="
            i < activeStep
              ? 'bg-emerald-700 text-white dark:bg-emerald-700'
              : i === activeStep
                ? 'bg-white/20'
                : 'bg-slate-200 dark:bg-ink/30'
          "
          >{{ i < activeStep ? "✓" : i + 1 }}</span
        >
        {{ step.label }}
      </button>
    </nav>

    <MetaStep
      v-show="activeStep === 0"
      :title="title"
      :template="template"
      :language="language"
      @update:title="emit('update:title', $event)"
      @update:template="emit('update:template', $event)"
      @update:language="emit('update:language', $event)"
    />
    <PersonalStep v-show="activeStep === 1" v-model="local" />
    <SummaryStep
      v-show="activeStep === 2"
      v-model="local"
      :cv-id="cvId"
      :ai-loading="aiLoading"
      :ai-error="aiError"
      @generate="generateSummary"
    />
    <ExperienceStep v-show="activeStep === 3" v-model="local" />
    <EducationStep v-show="activeStep === 4" v-model="local" />
    <OrganizationStep v-show="activeStep === 5" v-model="local" />
    <SkillsStep v-show="activeStep === 6" v-model="local" />
    <ProjectsStep v-show="activeStep === 7" v-model="local" />
    <CertificatesStep v-show="activeStep === 8" v-model="local" />
    <OtherStep v-show="activeStep === 9" v-model="local" />

    <!-- Step navigation -->
    <div class="flex items-center justify-between gap-3 pt-2">
      <button
        v-if="activeStep > 0"
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        @click="activeStep--"
      >
        ← Sebelumnya
      </button>
      <div v-else></div>
      <span class="text-xs text-slate-400 dark:text-foreground/60"
        >Langkah {{ activeStep + 1 }} / {{ steps.length }}</span
      >
      <button
        v-if="activeStep < steps.length - 1"
        type="button"
        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-main dark:hover:bg-blue-700"
        @click="activeStep++"
      >
        Selanjutnya →
      </button>
      <button
        v-else
        type="submit"
        :disabled="aiLoading"
        class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-40 dark:bg-main dark:hover:bg-blue-700"
      >
        Simpan CV
      </button>
    </div>
  </form>
</template>

<style>
/* Non-scoped tapi dinamespake .cv-form: field ada di child components (steps/form),
   scoped selector tidak menembus batas komponen Vue. */
.cv-form.dark nav,
.dark .cv-form nav {
  border-color: var(--border);
}
.dark .cv-form input,
.dark .cv-form select,
.dark .cv-form textarea {
  border-color: var(--border);
  background-color: color-mix(in srgb, var(--foreground) 12%, transparent);
  color: var(--foreground);
}
.dark .cv-form input::placeholder,
.dark .cv-form textarea::placeholder {
  color: color-mix(in srgb, var(--foreground) 80%, transparent);
}
.dark .cv-form input:focus,
.dark .cv-form select:focus,
.dark .cv-form textarea:focus {
  border-color: var(--ring);
}
.dark .cv-form select option {
  background-color: var(--secondary-background);
  color: var(--foreground);
}
.dark .cv-form select:hover {
  background-color: color-mix(in srgb, var(--foreground) 18%, transparent);
}
.dark .cv-form select option:hover,
.dark .cv-form select option:checked {
  background-color: var(--main);
  color: var(--main-foreground);
}
.dark .cv-form label > span {
  color: color-mix(in srgb, var(--foreground) 75%, transparent);
}
.dark .cv-form h2 {
  color: color-mix(in srgb, var(--foreground) 70%, transparent);
}
.dark .cv-form p {
  color: color-mix(in srgb, var(--foreground) 60%, transparent);
}
</style>
