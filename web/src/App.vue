<script setup lang="ts">
import { RouterView } from "vue-router";
import AppNavbar from "@/components/AppNavbar.vue";
import AppFooter from "@/components/AppFooter.vue";
import { useDarkMode } from "@/composables/useDarkMode";

const { initTheme } = useDarkMode();
initTheme();
</script>

<template>
  <div
    class="min-h-screen bg-paper text-ink dark:bg-background dark:text-foreground"
  >
    <AppNavbar />
    <RouterView />
    <AppFooter />
  </div>
</template>
