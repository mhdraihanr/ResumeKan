<script setup lang="ts">
// Bullet list CV: 1 baris = 1 bullet, marker slate-500.
defineProps<{ items: string[] }>();
</script>

<template>
  <ul
    v-if="items.length"
    class="mt-1.5 list-disc space-y-1 pl-5 text-[10pt] text-slate-700 marker:text-slate-500"
  >
    <li v-for="(b, j) in items" :key="j" class="leading-relaxed pl-1">
      {{ b }}
    </li>
  </ul>
</template>
