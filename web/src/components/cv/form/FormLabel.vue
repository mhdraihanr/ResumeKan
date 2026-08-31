<script setup lang="ts">
defineProps<{ label: string }>();
</script>

<template>
  <span class="text-xs font-medium text-slate-700">{{ label }}</span>
</template>
