<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";

const data = defineModel<CvData>({ required: true });

/** Peta path field -> pesan error inline, mis. `languages`. */
const props = defineProps<{ errors?: Record<string, string> }>();

function err(path: string): string | undefined {
  return props.errors?.[path];
}
</script>

<template>
  <section class="space-y-2.5">
    <h2
      class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-300"
    >
      Lainnya
    </h2>
    <label class="space-y-1 block">
      <FormLabel label="Bahasa" />
      <FormInput
        id="other-languages"
        v-model="data.languages"
        maxlength="200"
        placeholder="Indonesia (Native), English (Fluent)"
        :error="err('languages')"
      />
    </label>
  </section>
</template>
