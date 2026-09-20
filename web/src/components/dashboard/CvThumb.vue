<script setup lang="ts">
import { computed } from "vue";

/**
 * Miniatur dokumen CV berbasis proporsi A4 asli (794 x 1123 px).
 * Diskala 29% menjadi 230 x 326 px, lalu container luar memotongnya tepat di
 * rasio yang sama (79 x 112) supaya tidak ada area kosong di kartu.
 *
 * Bukan gambar atau screenshot palsu: ini ringkasan struktur dokumen yang
 * memang dipakai template (nama sebagai kepala, lalu baris section), jadi user
 * bisa mengenali dan memilih CV sebelum membukanya.
 */
const props = defineProps<{
  title: string;
  template: "modern" | "classic" | "neon";
}>();

/** Inisial judul CV (ditulis user sendiri), bukan foto orang. */
const initial = computed(
  () => props.title.trim().charAt(0).toUpperCase() || "C",
);

/**
 * Kepala dokumen mengikuti warna template aslinya (navy = h2 template modern,
 * ink = Neon, abu = Classic monokrom) supaya identitas template tetap terbaca
 * di daftar tanpa menambah warna baru ke palet UI. Classic memakai slate-600
 * (#45556c, 7.3:1 dengan teks putih), bukan slate-400 yang hanya 2.6:1.
 */
const headClass = computed(() => {
  switch (props.template) {
    case "modern":
      return "bg-navy";
    case "neon":
      return "bg-ink";
    default:
      return "bg-slate-600";
  }
});

/** Classic cenderung lebih padat, Neon lebih lega, jadi jumlah barisnya beda. */
const barCount = computed(() => (props.template === "neon" ? 5 : 6));

const bars = computed(() =>
  Array.from({ length: barCount.value }, (_, i) =>
    i % 3 === 2 ? "w-2/3" : "w-full",
  ),
);
</script>

<template>
  <!-- Container luar hanya memotong (crop), tanpa affordance apa pun -->
  <div
    class="relative size-full overflow-hidden ring-inset ring-ink/15 dark:ring-border/30"
    aria-hidden="true"
  >
    <!-- Lembar A4: 230 x 326 px didesain, lalu diskala 0.348 ke dalam 80 x 113 -->
    <div
      class="absolute left-0 top-0 flex w-[230px] origin-top-left scale-[0.348] flex-col gap-[14px] border-2 border-ink bg-white p-[20px] dark:border-border"
      style="height: 326px"
    >
      <!-- Kepala dokumen: inisial + garis nama -->
      <div
        class="flex items-center gap-[10px] border-b-2 border-slate-300 pb-[12px]"
      >
        <div
          class="flex size-[30px] shrink-0 items-center justify-center rounded-[3px] text-[15px] font-black text-white"
          :class="headClass"
        >
          {{ initial }}
        </div>
        <div class="flex flex-1 flex-col gap-[6px]">
          <div class="h-[9px] w-[70%] rounded-[2px] bg-slate-700" />
          <div class="h-[6px] w-[45%] rounded-[2px] bg-slate-300" />
        </div>
      </div>

      <!-- Baris section dokumen -->
      <div v-for="n in barCount" :key="n" class="flex flex-col gap-[6px]">
        <div class="h-[7px] w-[38%] rounded-[2px] bg-slate-500" />
        <div class="h-[5px] w-full rounded-[2px] bg-slate-200" />
        <div class="h-[5px] rounded-[2px] bg-slate-200" :class="bars[n - 1]" />
      </div>
    </div>
  </div>
</template>
