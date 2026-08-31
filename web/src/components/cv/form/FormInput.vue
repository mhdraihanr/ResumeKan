<script setup lang="ts">
defineProps<{
  placeholder?: string;
  type?: string;
  required?: boolean;
  maxlength?: number | string;
}>();

const model = defineModel<string>();
</script>

<template>
  <input
    v-model="model"
    :type="type ?? 'text'"
    :required="required"
    :maxlength="maxlength as number | undefined"
    :placeholder="placeholder"
    class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
  />
</template>
