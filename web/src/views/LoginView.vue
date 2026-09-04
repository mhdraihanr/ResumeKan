<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useDarkMode } from "@/composables/useDarkMode";
import { Eye, EyeOff, Moon, Sun } from "lucide-vue-next";

const auth = useAuthStore();
const router = useRouter();
const { isDark, cycle } = useDarkMode();

const email = ref("");
const password = ref("");
const showPassword = ref(false);

async function submit() {
  await auth.login(email.value, password.value);
  if (auth.isAuthenticated) router.push("/dashboard");
}
</script>

<template>
  <main class="grid min-h-screen lg:grid-cols-2">
    <!-- Panel kiri: pitch brand (desktop saja) -->
    <aside
      class="hidden flex-col justify-between border-r-2 border-ink bg-ink p-10 text-paper lg:flex dark:border-border"
    >
      <RouterLink to="/" class="text-xl font-black tracking-tight"
        >ResumeKan</RouterLink
      >
      <div>
        <h2 class="max-w-sm text-4xl font-black leading-tight tracking-tight">
          CV yang lolos
          <span class="bg-powder px-1 text-ink">ATS</span> dalam hitungan menit.
        </h2>
        <ul class="mt-8 space-y-3 text-sm text-paper/80">
          <li class="flex items-center gap-3">
            <span class="size-2 shrink-0 bg-powder"></span>
            3 template ATS, preview real-time
          </li>
          <li class="flex items-center gap-3">
            <span class="size-2 shrink-0 bg-powder"></span>
            Draft tersimpan, buka di device mana saja
          </li>
          <li class="flex items-center gap-3">
            <span class="size-2 shrink-0 bg-powder"></span>
            Download PDF A4 siap lamar
          </li>
        </ul>
      </div>
      <p class="text-xs text-paper/50">Gratis daftar, maksimal 10 CV.</p>
    </aside>

    <!-- Panel kanan: form -->
    <div
      class="bg-dots flex items-center justify-center bg-paper p-4 dark:bg-background"
    >
      <div
        class="relative w-full max-w-md rounded-base border-2 border-ink bg-white p-6 shadow-shadow dark:border-border dark:bg-secondary-background dark:text-foreground sm:p-8"
      >
        <button
          @click="cycle()"
          class="absolute right-4 top-4 flex size-9 items-center justify-center rounded-base border-2 border-ink bg-white shadow-shadow hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none dark:border-border dark:bg-secondary-background"
          :title="isDark() ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
          :aria-label="
            isDark() ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'
          "
          :aria-pressed="isDark() ? 'true' : 'false'"
        >
          <Sun v-if="isDark()" class="size-4 text-foreground" />
          <Moon v-else class="size-4 text-ink" />
        </button>
        <h1 class="text-2xl font-black text-ink dark:text-foreground">Masuk</h1>
        <p class="mb-6 mt-1 text-sm text-ink/70 dark:text-foreground/70">
          Belum punya akun?
          <RouterLink
            to="/register"
            class="font-bold text-navy underline decoration-2 underline-offset-2 dark:text-main"
            >Daftar</RouterLink
          >
        </p>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label
              for="email"
              class="mb-1 block text-sm font-bold text-ink dark:text-foreground"
              >Email</label
            >
            <input
              id="email"
              v-model="email"
              type="email"
              autocomplete="email"
              required
              class="w-full rounded-base border-2 border-ink bg-white px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-navy dark:border-border dark:bg-ink/20 dark:text-foreground dark:focus:ring-main"
            />
          </div>
          <div>
            <label
              for="password"
              class="mb-1 block text-sm font-bold text-ink dark:text-foreground"
              >Password</label
            >
            <div class="relative">
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                class="w-full rounded-base border-2 border-ink bg-white px-3 py-2 pr-10 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-navy dark:border-border dark:bg-ink/20 dark:text-foreground dark:focus:ring-main"
              />
              <button
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-ink/60 hover:text-ink dark:text-foreground/60 dark:hover:text-foreground"
                :aria-label="
                  showPassword ? 'Sembunyikan password' : 'Tampilkan password'
                "
                @click="showPassword = !showPassword"
              >
                <Eye v-if="showPassword" class="size-4" />
                <EyeOff v-else class="size-4" />
              </button>
            </div>
          </div>
          <button
            type="submit"
            :disabled="auth.loading"
            class="w-full rounded-base border-2 border-ink bg-navy px-3 py-2.5 text-sm font-bold text-white shadow-shadow transition hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none disabled:opacity-50 dark:border-border dark:bg-main dark:shadow-[4px_4px_0_0_#09090b]"
          >
            {{ auth.loading ? "Memproses..." : "Masuk" }}
          </button>
        </form>

        <p
          v-if="auth.error"
          aria-live="polite"
          class="mt-4 rounded-base border-2 border-error bg-red-50 p-3 text-sm font-medium text-error dark:bg-red-900/20 dark:text-red-400"
        >
          {{ auth.error }}
        </p>
      </div>
    </div>
  </main>
</template>
