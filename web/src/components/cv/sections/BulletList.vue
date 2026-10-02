<script setup lang="ts">
// Bullet list CV: 1 baris = 1 bullet, teks neutral-950, marker neutral-800.
defineProps<{ items: string[] }>();
</script>

<template>
  <ul
    v-if="items.length"
    class="mt-1 list-disc space-y-0.5 pl-5 text-[10pt] text-neutral-950 marker:text-neutral-800"
  >
    <li v-for="(b, j) in items" :key="j" class="leading-normal pl-1">
      {{ b }}
    </li>
  </ul>
</template>
