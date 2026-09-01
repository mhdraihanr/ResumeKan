<script setup lang="ts">
defineProps<{
  placeholder?: string;
  maxlength?: number | string;
  rows?: number | string;
}>();

const model = defineModel<string>();

function autoResize(e: Event) {
  const el = e.target as HTMLTextAreaElement;
  if (CSS.supports("field-sizing", "content")) return;
  el.style.height = "auto";
  el.style.height = el.scrollHeight + "px";
}
</script>

<template>
  <textarea
    v-model="model"
    :placeholder="placeholder"
    :maxlength="maxlength as number | undefined"
    :rows="(rows as number | undefined) ?? 3"
    class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
    @input="autoResize"
  ></textarea>
</template>

<style scoped>
.auto-expand {
  field-sizing: content;
  min-block-size: 3lh;
  max-block-size: 12lh;
  overflow-y: auto;
  resize: vertical;
}
@supports not (field-sizing: content) {
  .auto-expand {
    min-height: 72px;
  }
}
</style>
