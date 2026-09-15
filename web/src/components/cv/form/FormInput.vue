<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  placeholder?: string;
  type?: string;
  required?: boolean;
  maxlength?: number | string;
  /** Pesan error inline; kosong berarti field valid. */
  error?: string;
  /** Id unik untuk mengaitkan error ke input via aria-describedby. */
  id?: string;
}>();

const model = defineModel<string>();

const errorId = computed(() => (props.id ? `${props.id}-error` : undefined));
</script>

<template>
  <input
    v-model="model"
    :id="id"
    :type="type ?? 'text'"
    :required="required"
    :maxlength="maxlength as number | undefined"
    :placeholder="placeholder"
    :aria-invalid="error ? 'true' : undefined"
    :aria-describedby="error ? errorId : undefined"
    class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
    :class="error ? 'border-red-500 dark:border-red-500' : ''"
  />
  <p
    v-if="error"
    :id="errorId"
    class="text-[11px] font-medium text-red-700 dark:text-red-300"
  >
    {{ error }}
  </p>
</template>
