<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import type { CvData } from "@/types/cv";
import { cvApi } from "@/api/cv";
import {
  collectInvalid,
  collectMissing,
  mapServerErrors,
  messageFor,
  pruneEmptyEntries,
} from "@/lib/cv-validation";
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
  "update:removedEntries": [count: number];
  submit: [];
}>();

const local = defineModel<CvData>("modelValue", { required: true });
const fontFamily = defineModel<string>("fontFamily", { default: "default" });
const fontSize = defineModel<string>("fontSize", { default: "default" });

const aiLoading = ref(false);
const aiError = ref("");

/**
 * Error inline per field, `path` -> pesan. Sumbernya dua: validasi klien saat
 * submit, dan payload 422 dari server (dipetakan di `cv-validation.ts`).
 * Error tetap tampil sampai field diperbaiki, bukan hilang sendiri.
 */
const fieldErrors = ref<Record<string, string>>({});
/** Pesan ringkas di atas tombol Simpan saat submit ditolak. */
const submitError = ref("");

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

function err(path: string): string | undefined {
  return fieldErrors.value[path];
}

/**
 * Step yang punya minimal satu error — dipakai untuk penanda di stepper.
 * Baru aktif setelah percobaan simpan pertama: sebelum itu pengguna belum
 * diberi tahu apa pun, jadi menandai field kosong justru salah sasaran.
 */
const submitAttempted = ref(false);
const errorSteps = computed(() => {
  if (!submitAttempted.value) return new Set<number>();
  const missing = collectMissing(local.value, props.title).map((m) => m.step);
  const invalid = collectInvalid(local.value).map((m) => m.step);
  return new Set([...missing, ...invalid]);
});

/**
 * Cek kelengkapan sebelum kirim. Kalau ada yang kurang: tampilkan error inline,
 * pindah ke step pemilik error pertama, dan kembalikan `false`.
 *
 * Fokus diarahkan ke heading step (bukan langsung ke input) supaya pengguna
 * tahu ia berpindah tempat sebelum dibawa ke field — lompatan diam terasa
 * seperti form kehilangan posisi.
 */
async function validateAndFocus(): Promise<boolean> {
  submitAttempted.value = true;
  const missing = collectMissing(local.value, props.title);
  const invalid = collectInvalid(local.value);
  const next: Record<string, string> = {};
  for (const m of missing) next[m.path] = messageFor(m.label);
  for (const m of invalid) next[m.path] = m.message;
  fieldErrors.value = next;

  const total = missing.length + invalid.length;
  if (total === 0) {
    submitError.value = "";
    return true;
  }

  submitError.value = `Ada ${total} isian yang perlu diperbaiki. Cek penanda merah di bawah.`;
  const first = [...missing, ...invalid].sort((a, b) => a.step - b.step)[0]!;
  const firstStep = first.step;

  if (firstStep === activeStep.value) {
    // Sudah di step yang bermasalah: langsung ke field pertama yang invalid.
    await nextTick();
    document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus();
  } else {
    activeStep.value = firstStep;
    await nextTick();
    await focusStepHeading();
  }

  return false;
}

/** Fokus ke heading step aktif agar perpindahan step terbaca, bukan lompatan diam. */
async function focusStepHeading(): Promise<void> {
  const heading = document.querySelector<HTMLElement>(".cv-step-active h2");
  if (!heading) return;
  heading.setAttribute("tabindex", "-1");
  heading.focus();
}

/** Buang entri kosong (klik "+ Tambah" lalu batal bukan kesalahan). */
function pruneEntries(): void {
  const { data, removed } = pruneEmptyEntries(local.value);
  local.value = data;
  emit("update:removedEntries", removed);
}

/** Dipanggil `CvFormView` sebelum menyusun payload submit final. */
async function prepareSubmit(): Promise<boolean> {
  // Prune DULU: entri kosong tidak boleh ikut divalidasi dan memunculkan error palsu.
  pruneEntries();

  return validateAndFocus();
}

/**
 * Cek kelengkapan TANPA efek samping (tidak prune, tidak ubah error, tidak
 * pindah step). Dipakai `CvFormView` untuk memutuskan buka/tidak buka tab PDF
 * secara sinkron — sehingga window tidak pernah dibuka kalau data kurang.
 */
function isComplete(): boolean {
  return (
    collectMissing(local.value, props.title).length === 0 &&
    collectInvalid(local.value).length === 0
  );
}

/**
 * Validasi FORMAT saja (email/telepon) — dipakai draft, yang boleh kurang isi
 * tapi tetap harus bentuknya benar bila ada isinya. Menampilkan error inline
 * dan pindah ke step bermasalah; kembalikan `false` bila ada yang salah.
 */
async function checkFormats(): Promise<boolean> {
  const invalid = collectInvalid(local.value);
  if (invalid.length === 0) {
    // Bersihkan error format yang mungkin tersisa tanpa mengusik error lain.
    const next: Record<string, string> = {};
    for (const [path, msg] of Object.entries(fieldErrors.value)) {
      if (!invalid.some((i) => i.path === path)) next[path] = msg;
    }
    fieldErrors.value = next;
    return true;
  }

  submitAttempted.value = true;
  const next: Record<string, string> = { ...fieldErrors.value };
  for (const m of invalid) next[m.path] = m.message;
  fieldErrors.value = next;
  submitError.value = `Ada ${invalid.length} isian yang perlu diperbaiki. Cek penanda merah di bawah.`;

  const firstStep = invalid[0]!.step;
  if (firstStep !== activeStep.value) {
    activeStep.value = firstStep;
    await nextTick();
    await focusStepHeading();
  } else {
    await nextTick();
    document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus();
  }

  return false;
}

/** Terapkan error 422 dari server ke field yang bersangkutan. */
async function applyServerErrors(errors: unknown): Promise<void> {
  submitAttempted.value = true;
  const mapped = mapServerErrors(errors);
  fieldErrors.value = mapped;

  const paths = Object.keys(mapped);
  if (paths.length > 0) {
    submitError.value =
      "Ada isian yang perlu diperbaiki. Cek penanda merah di bawah.";
    const candidates = [
      ...collectMissing(local.value, props.title),
      ...collectInvalid(local.value),
    ];
    const first = candidates.find((m) => paths.includes(m.path));
    if (first && first.step !== activeStep.value) {
      activeStep.value = first.step;
      await nextTick();
      await focusStepHeading();
    }
  }
}

defineExpose({
  prepareSubmit,
  pruneEntries,
  applyServerErrors,
  isComplete,
  checkFormats,
});

/**
 * Setelah percobaan simpan, error dihapus begitu field-nya diperbaiki —
 * pengguna melihat perbaikannya langsung mendarat, tanpa harus submit ulang.
 */
watch(
  () => [local.value, props.title] as const,
  () => {
    if (!submitAttempted.value) return;
    const valid = new Set([
      ...collectMissing(local.value, props.title).map((m) => m.path),
      ...collectInvalid(local.value).map((m) => m.path),
    ]);
    const next: Record<string, string> = {};
    for (const [path, msg] of Object.entries(fieldErrors.value)) {
      if (valid.has(path)) next[path] = msg;
    }
    fieldErrors.value = next;
    if (Object.keys(next).length === 0) submitError.value = "";
  },
  { deep: true },
);

async function generateSummary(jobDescription?: string) {
  if (!props.cvId) {
    aiError.value = "Simpan CV dulu sebelum generate.";
    return;
  }
  aiLoading.value = true;
  aiError.value = "";
  try {
    const res = await cvApi.aiSummary(
      props.cvId,
      props.language,
      local.value,
      jobDescription,
    );
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
  <form class="cv-form space-y-6" novalidate @submit.prevent="emit('submit')">
    <!-- Stepper nav -->
    <nav
      class="flex flex-wrap gap-1.5 border-b border-slate-200 pb-3 dark:border-border"
    >
      <button
        v-for="(step, i) in steps"
        :key="step.key"
        type="button"
        class="relative flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition"
        :class="
          i === activeStep
            ? 'bg-slate-900 text-white dark:bg-main'
            : i < activeStep
              ? 'text-slate-700 hover:bg-slate-200 dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground'
              : 'text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:text-foreground/75 dark:hover:bg-white/15 dark:hover:text-foreground'
        "
        :aria-current="i === activeStep ? 'step' : undefined"
        @click="activeStep = i"
      >
        <span
          class="size-4 rounded-full text-center text-[10px] leading-4"
          :class="
            errorSteps.has(i)
              ? 'bg-red-600 text-white'
              : i < activeStep
                ? 'bg-emerald-700 text-white dark:bg-emerald-700'
                : i === activeStep
                  ? 'bg-white/20'
                  : 'bg-slate-200 dark:bg-ink/30'
          "
          >{{ errorSteps.has(i) ? "!" : i < activeStep ? "✓" : i + 1 }}</span
        >
        {{ step.label }}
      </button>
    </nav>

    <!-- Ringkasan error: memberi gambaran cakupan sebelum pengguna menelusuri -->
    <p
      v-if="submitError"
      role="alert"
      class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300"
    >
      {{ submitError }}
    </p>

    <div :class="activeStep === 0 ? 'cv-step-active' : ''">
      <MetaStep
        v-show="activeStep === 0"
        :title="title"
        :template="template"
        :language="language"
        v-model:font-family="fontFamily"
        v-model:font-size="fontSize"
        :title-error="err('title')"
        @update:title="emit('update:title', $event)"
        @update:template="emit('update:template', $event)"
        @update:language="emit('update:language', $event)"
      />
    </div>
    <div :class="activeStep === 1 ? 'cv-step-active' : ''">
      <PersonalStep
        v-show="activeStep === 1"
        v-model="local"
        :errors="fieldErrors"
      />
    </div>
    <SummaryStep
      v-show="activeStep === 2"
      v-model="local"
      :cv-id="cvId"
      :ai-loading="aiLoading"
      :ai-error="aiError"
      @generate="generateSummary"
    />
    <div :class="activeStep === 3 ? 'cv-step-active' : ''">
      <ExperienceStep
        v-show="activeStep === 3"
        v-model="local"
        :errors="fieldErrors"
      />
    </div>
    <div :class="activeStep === 4 ? 'cv-step-active' : ''">
      <EducationStep
        v-show="activeStep === 4"
        v-model="local"
        :errors="fieldErrors"
      />
    </div>
    <div :class="activeStep === 5 ? 'cv-step-active' : ''">
      <OrganizationStep
        v-show="activeStep === 5"
        v-model="local"
        :errors="fieldErrors"
      />
    </div>
    <SkillsStep v-show="activeStep === 6" v-model="local" />
    <div :class="activeStep === 7 ? 'cv-step-active' : ''">
      <ProjectsStep
        v-show="activeStep === 7"
        v-model="local"
        :errors="fieldErrors"
      />
    </div>
    <div :class="activeStep === 8 ? 'cv-step-active' : ''">
      <CertificatesStep
        v-show="activeStep === 8"
        v-model="local"
        :errors="fieldErrors"
      />
    </div>
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
      <span class="text-xs text-slate-500 dark:text-foreground/75"
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
   scoped selector tidak menembus batas komponen Vue.

   WAJIB di dalam @layer components: CSS yang tidak berada di layer mana pun
   selalu MENANG atas CSS ber-layer (utilities Tailwind) tanpa peduli
   specificity. Tanpa layer ini, aturan `.dark .cv-form p` akan menimpa
   utility pewarnaan khusus seperti `dark:text-red-300` pada pesan error. */
@layer components {
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
}
</style>
