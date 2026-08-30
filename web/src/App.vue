<script setup lang="ts">
import { RouterView, useRoute } from "vue-router";
import AppNavbar from "@/components/AppNavbar.vue";
import AppFooter from "@/components/AppFooter.vue";
import { useDarkMode } from "@/composables/useDarkMode";

const { initTheme } = useDarkMode();
initTheme();

// Auth pages (login/register): full-screen tanpa navbar/footer (layout minimal)
const route = useRoute();
</script>

<template>
  <div
    class="min-h-screen bg-paper text-ink dark:bg-background dark:text-foreground"
  >
    <AppNavbar v-if="!route.meta.authLayout" />
    <RouterView />
    <AppFooter v-if="!route.meta.authLayout" />
  </div>
</template>
