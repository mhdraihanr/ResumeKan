<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";

const data = defineModel<CvData>({ required: true });
</script>

<template>
  <section class="space-y-2.5">
    <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500">
      Lainnya
    </h2>
    <label class="space-y-1 block">
      <FormLabel label="Bahasa" />
      <FormInput
        v-model="data.languages"
        maxlength="200"
        placeholder="Indonesia (Native), English (Fluent)"
      />
    </label>
  </section>
</template>
