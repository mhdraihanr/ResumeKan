<script setup lang="ts">
import { computed } from "vue";
import type { CvData } from "@/types/cv";
import { getTemplateConfig } from "@/lib/cv-templates";
import CvModern from "./templates/CvModern.vue";
import CvClassic from "./templates/CvClassic.vue";
import CvNeon from "./templates/CvNeon.vue";

const props = withDefaults(
  defineProps<{
    data: CvData;
    template: string;
    language?: string;
    compact?: boolean;
  }>(),
  { compact: false, language: "id" },
);

const tpl = computed(() => getTemplateConfig(props.template));
const comp = computed(() =>
  tpl.value.id === "neon"
    ? CvNeon
    : tpl.value.id === "classic"
      ? CvClassic
      : CvModern,
);
</script>

<template>
  <div
    :id="`cv-preview-${tpl.id}`"
    :class="[
      'cv-paper mx-auto w-full max-w-[800px] bg-white text-slate-900 antialiased',
      tpl.font,
    ]"
    style="font-size: 11pt; line-height: 1.5"
  >
    <div :class="['cv-page', compact ? 'px-0 py-4' : 'px-8 py-8 sm:px-10']">
      <component :is="comp" :data="data" :language="language" />
    </div>
  </div>
</template>

<style>
@media print {
  .cv-paper {
    max-width: none !important;
  }
  .cv-page {
    padding: 0 !important;
  }
  @page {
    size: A4;
    margin: 14mm 16mm;
  }
  a {
    color: inherit !important;
    text-decoration: none !important;
  }
}
</style>
