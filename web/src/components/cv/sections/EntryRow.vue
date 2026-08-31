<script setup lang="ts">
// Baris entri: judul kiri + tanggal kanan (baseline align).
// classic pakai tabular-nums agar angka tahun rata.
defineProps<{ title: string; period: string; modern?: boolean }>();
</script>

<template>
  <div class="flex items-baseline justify-between gap-4">
    <p class="text-[10pt] font-semibold text-slate-900">{{ title }}</p>
    <p
      :class="[
        'shrink-0 text-[9pt] text-slate-500',
        modern ? '' : 'tabular-nums',
      ]"
    >
      {{ period }}
    </p>
  </div>
</template>
