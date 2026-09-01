<script setup lang="ts">
defineProps<{ label: string }>();
</script>

<template>
  <span class="text-xs font-medium text-slate-700 dark:text-foreground/75">{{
    label
  }}</span>
</template>
