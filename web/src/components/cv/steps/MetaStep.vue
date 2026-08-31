<script setup lang="ts">
import FormLabel from "../form/FormLabel.vue";
import { CV_TEMPLATES } from "@/lib/cv-templates";

defineProps<{ title: string; template: string; language: string }>();
defineEmits<{
  "update:title": [v: string];
  "update:template": [v: string];
  "update:language": [v: string];
}>();
</script>

<template>
  <section class="space-y-2.5">
    <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500">
      Info CV
    </h2>
    <div class="grid gap-2.5 sm:grid-cols-3">
      <label class="space-y-1">
        <FormLabel label="Judul CV *" />
        <input
          :value="title"
          required
          maxlength="100"
          placeholder="CV Backend"
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
          @input="
            $emit('update:title', ($event.target as HTMLInputElement).value)
          "
        />
      </label>
      <label class="space-y-1">
        <FormLabel label="Template" />
        <select
          :value="template"
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
          @change="
            $emit('update:template', ($event.target as HTMLSelectElement).value)
          "
        >
          <option
            v-for="t in Object.values(CV_TEMPLATES)"
            :key="t.id"
            :value="t.id"
          >
            {{ t.label }}
          </option>
        </select>
      </label>
      <label class="space-y-1">
        <FormLabel label="Bahasa" />
        <select
          :value="language"
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
          @change="
            $emit('update:language', ($event.target as HTMLSelectElement).value)
          "
        >
          <option value="id">Indonesia</option>
          <option value="en">English</option>
        </select>
      </label>
    </div>
  </section>
</template>
