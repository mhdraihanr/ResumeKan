<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  placeholder?: string;
  maxlength?: number | string;
  rows?: number | string;
  /** Pesan error inline; kosong berarti field valid. */
  error?: string;
  /** Id unik untuk mengaitkan error ke textarea via aria-describedby. */
  id?: string;
}>();

const model = defineModel<string>();

const errorId = computed(() => (props.id ? `${props.id}-error` : undefined));

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
    :id="id"
    :placeholder="placeholder"
    :maxlength="maxlength as number | undefined"
    :rows="(rows as number | undefined) ?? 3"
    :aria-invalid="error ? 'true' : undefined"
    :aria-describedby="error ? errorId : undefined"
    class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
    :class="error ? 'border-red-500 dark:border-red-500' : ''"
    @input="autoResize"
  ></textarea>
  <p
    v-if="error"
    :id="errorId"
    class="text-[11px] font-medium text-red-700 dark:text-red-300"
  >
    {{ error }}
  </p>
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
