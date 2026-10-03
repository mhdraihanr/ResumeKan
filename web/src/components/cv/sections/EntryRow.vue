<script setup lang="ts">
// Baris entri: judul kiri + tanggal kanan (baseline align).
// Judul (T1) mengikuti `inkClass` template; tanggal (T3) tetap neutral-800.
// classic pakai tabular-nums agar angka tahun rata.
withDefaults(
  defineProps<{
    title: string;
    period: string;
    modern?: boolean;
    inkClass?: string;
  }>(),
  { inkClass: "text-neutral-950" },
);
</script>

<template>
  <div class="flex items-baseline justify-between gap-4">
    <p class="text-[10pt] font-bold" :class="inkClass">{{ title }}</p>
    <p
      :class="[
        'shrink-0 text-[9pt] text-neutral-800',
        modern ? '' : 'tabular-nums',
      ]"
    >
      {{ period }}
    </p>
  </div>
</template>
