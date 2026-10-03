<script setup lang="ts">
// Bullet list CV: 1 baris = 1 bullet. Warna teks (T2) mengikuti `inkClass`
// template (modern `text-neutral-950`, classic/neon `text-black`). Warna marker
// mengikuti `markerClass` (default T3 `text-neutral-800`; neon `#000`).
withDefaults(
  defineProps<{ items: string[]; inkClass?: string; markerClass?: string }>(),
  { inkClass: "text-neutral-950", markerClass: "marker:text-neutral-800" },
);
</script>

<template>
  <ul
    v-if="items.length"
    class="mt-1 list-disc space-y-0.5 pl-5 text-[10pt]"
    :class="[inkClass, markerClass]"
  >
    <li v-for="(b, j) in items" :key="j" class="leading-normal pl-1">
      {{ b }}
    </li>
  </ul>
</template>
