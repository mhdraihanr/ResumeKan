<script setup lang="ts">
import AppLogo from "@/components/AppLogo.vue";
</script>

<template>
  <footer
    class="border-t-2 border-ink bg-paper dark:border-border dark:bg-background"
  >
    <div
      class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-4 text-sm text-ink/80 dark:text-slate-300 sm:flex-row sm:px-6"
    >
      <p class="flex items-center gap-2 font-medium">
        <AppLogo :height="18" />
        <span aria-hidden="true">·</span>
        <span>Generator CV ATS</span>
      </p>
      <p>Dibuat dengan kerja keras, tanpa klaim palsu.</p>
    </div>
  </footer>
</template>
