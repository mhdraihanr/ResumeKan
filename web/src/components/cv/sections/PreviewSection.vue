<script setup lang="ts">
import { computed } from "vue";
import { getTemplateConfig } from "@/lib/cv-templates";

const props = withDefaults(
  defineProps<{
    title: string;
    modern?: boolean;
    template?: string;
    inkClass?: string;
    ruleClass?: string;
  }>(),
  { inkClass: "text-neutral-950", ruleClass: "border-neutral-950" },
);
const cfg = computed(() =>
  props.template ? getTemplateConfig(props.template) : null,
);
const isNeon = computed(
  () => cfg.value?.id === "neon" || props.template === "neon",
);
const isModern = computed(() => props.modern || cfg.value?.id === "modern");
</script>

<template>
  <h2
    :class="[
      'pb-1 font-extrabold uppercase tracking-widest',
      inkClass,
      isNeon
        ? 'border-b-2 border-[#14b8a6] text-[11pt] text-[#0f766e]'
        : isModern
          ? 'border-b-2 border-[#1e40af] text-[11pt]'
          : ['border-b-[1.5px] text-[11pt]', ruleClass],
    ]"
  >
    {{ title }}
  </h2>
</template>
