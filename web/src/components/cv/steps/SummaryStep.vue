<script setup lang="ts">
import { ref } from "vue";
import type { CvData } from "@/types/cv";
import FormTextarea from "../form/FormTextarea.vue";

defineProps<{ cvId?: number; aiLoading: boolean; aiError: string }>();
const emit = defineEmits<{ (e: "generate", jobDescription?: string): void }>();

const data = defineModel<CvData>({ required: true });

const showTailor = ref(false);
const jobDescription = ref("");

function handleGenerate() {
  const jd = jobDescription.value.trim();
  emit("generate", jd || undefined);
}
</script>

<template>
  <section class="space-y-3">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-300"
      >
        Ringkasan
      </h2>
      <button
        v-if="cvId && !showTailor"
        type="button"
        class="rounded-lg bg-slate-900 px-3 py-1 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-40 dark:bg-main dark:hover:bg-blue-700"
        :disabled="aiLoading"
        @click="handleGenerate"
      >
        {{ aiLoading ? "Memproses..." : "Generate AI" }}
      </button>
    </div>

    <!-- AI Tailoring box (opsional target lowongan) -->
    <div
      v-if="cvId"
      class="rounded-lg border border-slate-200 bg-slate-50/75 p-2.5 text-xs dark:border-border dark:bg-secondary-background/60"
    >
      <div class="flex flex-wrap items-center justify-between gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 font-medium text-slate-700 hover:text-slate-900 focus:outline-none dark:text-foreground dark:hover:text-white"
          :aria-expanded="showTailor"
          @click="showTailor = !showTailor"
        >
          <svg
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.75a.75.75 0 00-1.5 0v3.5a.75.75 0 00.75.75h3.5a.75.75 0 000-1.5h-2.75V6.75z"
              clip-rule="evenodd"
            />
          </svg>
          <span>Sesuaikan dengan Target Lowongan (ATS Tailoring)</span>
          <span
            class="rounded bg-emerald-100 px-1.5 py-0.2 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
          >
            Opsional
          </span>
          <svg
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-3.5 w-3.5 text-slate-400 transition-transform dark:text-slate-300"
            :class="showTailor ? 'rotate-180' : ''"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
              clip-rule="evenodd"
            />
          </svg>
        </button>

        <button
          v-if="showTailor"
          type="button"
          class="rounded-lg bg-slate-900 px-3 py-1 font-medium text-white hover:bg-slate-800 disabled:opacity-40 dark:bg-main dark:hover:bg-blue-700"
          :disabled="aiLoading"
          @click="handleGenerate"
        >
          {{
            aiLoading
              ? "Memproses..."
              : jobDescription.trim()
                ? "Generate dengan Lowongan"
                : "Generate AI"
          }}
        </button>
      </div>

      <div
        v-show="showTailor"
        class="mt-2 space-y-1.5 border-t border-slate-200/80 pt-2 dark:border-border/80"
      >
        <p class="text-[11px] text-slate-500 dark:text-slate-300">
          Tempel syarat posisi atau deskripsi lowongan kerja. AI akan
          menyelaraskan kata kunci ATS dengan pengalaman aslimu tanpa mengarang
          fakta.
        </p>
        <textarea
          v-model="jobDescription"
          rows="3"
          maxlength="1500"
          placeholder="Contoh: Dicari Senior Frontend Engineer yang menguasai Vue 3, TypeScript, Tailwind CSS, dan integrasi REST API..."
          class="w-full rounded-md border border-slate-300 bg-white p-2 text-xs focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
        />
        <div
          class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-300"
        >
          <span>{{ jobDescription.length }}/1500 karakter</span>
          <button
            v-if="jobDescription"
            type="button"
            class="text-slate-500 underline hover:text-red-500 dark:text-slate-300 dark:hover:text-red-300"
            @click="jobDescription = ''"
          >
            Hapus teks
          </button>
        </div>
      </div>
    </div>

    <p v-if="aiError" class="text-xs text-red-600 dark:text-red-300">
      {{ aiError }}
    </p>
    <FormTextarea
      v-model="data.summary"
      maxlength="600"
      rows="3"
      placeholder="Ringkasan profesional singkat... (klik Generate AI jika CV sudah tersimpan)"
    />
    <p class="text-right text-xs text-slate-500 dark:text-slate-300">
      {{ (data.summary ?? "").length }}/600
    </p>
  </section>
</template>
