<script setup lang="ts">
/**
 * AppLogo — wordmark "ResumeKan" berbasis gambar.
 *
 * Satu aset varian gelap (huruf putih) dipakai di semua latar; CSS filter
 * ditolak karena invert() merusak gradasi powder.
 *
 * Props:
 * - height: tinggi huruf dalam px (aset sudah di-trim, jadi 28 = huruf 28px).
 * - tone:   tidak lagi mengubah aset, hanya jaga kompatibilitas pemanggil.
 */
import logoDark from "@/assets/brand/logo-navbar-dark.png";

withDefaults(
  defineProps<{
    height?: number;
    tone?: "auto" | "on-dark";
  }>(),
  { height: 28, tone: "auto" },
);
</script>

<template>
  <img
    :src="logoDark"
    alt="ResumeKan"
    :style="{ height: `${height}px` }"
    class="w-auto select-none"
    draggable="false"
  />
</template>
