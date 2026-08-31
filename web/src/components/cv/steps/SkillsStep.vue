<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormTextarea from "../form/FormTextarea.vue";

const data = defineModel<CvData>({ required: true });
</script>

<template>
  <section class="space-y-2.5">
    <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500">
      Keahlian
    </h2>
    <label class="space-y-1 block">
      <FormLabel label="Hard skills (pisah koma)" />
      <FormTextarea
        v-model="data.skills!.hard"
        maxlength="500"
        rows="2"
        placeholder="Go, Laravel, PostgreSQL, Docker, Redis, REST API, Git, CI/CD"
      />
    </label>
    <label class="space-y-1 block">
      <FormLabel label="Soft skills (pisah koma)" />
      <FormTextarea
        v-model="data.skills!.soft"
        maxlength="300"
        rows="2"
        placeholder="Komunikasi, Leadership, Stakeholder Management, Problem Solving"
      />
    </label>
  </section>
</template>
