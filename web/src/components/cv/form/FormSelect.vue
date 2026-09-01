<script setup lang="ts">
const model = defineModel<string>();
</script>

<template>
  <select
    v-model="model"
    class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
  >
    <slot />
  </select>
</template>
