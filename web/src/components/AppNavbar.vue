<script setup lang="ts">
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import { useDarkMode } from "@/composables/useDarkMode";
import { Moon, Sun, Monitor, Menu, X } from "lucide-vue-next";
import { ref } from "vue";

const auth = useAuthStore();
const router = useRouter();
const { choice, isDark, cycle } = useDarkMode();
const open = ref(false);

// Label ikut pilihan aktif, bukan hasil: ikon & aria mencerminkan state saat ini (a11y R-27)
const icon = () =>
  choice.value === "auto" ? Monitor : isDark() ? Moon : Sun;
const label = () =>
  choice.value === "auto" ? "Mode: ikuti sistem" : isDark() ? "Mode gelap" : "Mode terang";
</script>

<template>
  <nav
    class="sticky top-0 z-40 border-b-2 border-ink bg-paper/95 backdrop-blur-sm dark:border-border dark:bg-background/95"
  >
    <div
      class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6"
    >
      <RouterLink to="/" class="text-lg font-black tracking-tight text-ink dark:text-foreground">
        ResumeKan
      </RouterLink>

      <!-- Desktop -->
      <div class="hidden items-center gap-4 sm:flex">
        <RouterLink
          to="/"
          class="text-sm font-medium text-ink/70 hover:text-ink dark:text-foreground/70 dark:hover:text-foreground"
        >
          Beranda
        </RouterLink>
        <RouterLink
          v-if="auth.isAuthenticated"
          to="/dashboard"
          class="text-sm font-medium text-ink/70 hover:text-ink dark:text-foreground/70 dark:hover:text-foreground"
        >
          Dashboard
        </RouterLink>
        <template v-if="!auth.isAuthenticated">
          <RouterLink
            to="/login"
            class="rounded-base border-2 border-ink bg-white px-3 py-1.5 text-sm font-medium text-ink shadow-shadow hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none dark:border-border dark:bg-secondary-background dark:text-foreground"
          >
            Masuk
          </RouterLink>
          <RouterLink
            to="/register"
            class="rounded-base border-2 border-ink bg-navy px-3 py-1.5 text-sm font-medium text-white shadow-shadow hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none"
          >
            Daftar
          </RouterLink>
        </template>
        <button
          v-else
          @click="
            auth.logout();
            router.push('/');
          "
            class="rounded-base border-2 border-ink bg-white px-3 py-1.5 text-sm font-medium text-ink shadow-shadow hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none dark:border-border dark:bg-secondary-background dark:text-foreground"
        >
          Logout
        </button>
        <button
          @click="cycle()"
          class="flex size-9 items-center justify-center rounded-base border-2 border-ink bg-white shadow-shadow hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none dark:border-border dark:bg-secondary-background"
          :title="label()"
          :aria-label="label()"
          :aria-pressed="choice !== 'auto' ? 'true' : 'false'"
        >
          <component :is="icon()" class="size-4 text-ink dark:text-foreground" />
        </button>
      </div>

      <!-- Mobile toggle -->
      <button
        @click="open = !open"
        class="flex size-9 items-center justify-center rounded-base border-2 border-ink bg-white sm:hidden dark:border-border dark:bg-secondary-background"
      >
        <X v-if="open" class="size-4 text-ink dark:text-foreground" />
        <Menu v-else class="size-4 text-ink dark:text-foreground" />
      </button>
    </div>

    <!-- Mobile menu -->
    <div
      v-if="open"
      class="border-t-2 border-ink bg-paper px-4 pb-4 pt-2 sm:hidden dark:border-border dark:bg-background"
    >
      <div class="flex flex-col gap-2">
        <RouterLink
          to="/"
          class="rounded-base px-3 py-2 text-sm font-medium text-ink hover:bg-ink/5 dark:text-foreground dark:hover:bg-ink/20"
          @click="open = false"
        >
          Beranda
        </RouterLink>
        <RouterLink
          v-if="auth.isAuthenticated"
          to="/dashboard"
          class="rounded-base px-3 py-2 text-sm font-medium text-ink hover:bg-ink/5 dark:text-foreground dark:hover:bg-ink/20"
          @click="open = false"
        >
          Dashboard
        </RouterLink>
        <template v-if="!auth.isAuthenticated">
          <RouterLink
            to="/login"
            class="rounded-base border-2 border-ink bg-white px-3 py-2 text-center text-sm font-medium text-ink dark:border-border dark:bg-secondary-background dark:text-foreground"
            @click="open = false"
          >
            Masuk
          </RouterLink>
          <RouterLink
            to="/register"
            class="rounded-base border-2 border-ink bg-navy px-3 py-2 text-center text-sm font-medium text-white"
            @click="open = false"
          >
            Daftar
          </RouterLink>
        </template>
        <button
          @click="cycle()"
          class="flex items-center gap-2 rounded-base px-3 py-2 text-sm font-medium text-ink hover:bg-ink/5 dark:text-foreground dark:hover:bg-ink/20"
        >
          <component :is="icon()" class="size-4" />
          {{ label() }}
        </button>
      </div>
    </div>
  </nav>
</template>
