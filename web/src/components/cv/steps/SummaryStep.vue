<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormTextarea from "../form/FormTextarea.vue";

defineProps<{ cvId?: number; aiLoading: boolean; aiError: string }>();
defineEmits<{ generate: [] }>();

const data = defineModel<CvData>({ required: true });
</script>

<template>
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Ringkasan
      </h2>
      <button
        v-if="cvId"
        type="button"
        class="rounded-lg bg-slate-900 px-3 py-1 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-40 dark:bg-main dark:hover:bg-blue-700"
        :disabled="aiLoading"
        @click="$emit('generate')"
      >
        {{ aiLoading ? "Memproses..." : "Generate AI" }}
      </button>
    </div>
    <p v-if="aiError" class="text-xs text-red-600 dark:text-red-400">
      {{ aiError }}
    </p>
    <FormTextarea
      v-model="data.summary"
      maxlength="600"
      rows="3"
      placeholder="Ringkasan profesional singkat... (klik Generate AI jika CV sudah tersimpan)"
    />
    <p class="text-right text-xs text-slate-400">
      {{ (data.summary ?? "").length }}/600
    </p>
  </section>
</template>
