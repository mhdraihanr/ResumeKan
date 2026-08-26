<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const router = useRouter();

const email = ref("");
const password = ref("");

async function submit() {
  await auth.login(email.value, password.value);
  if (auth.isAuthenticated) router.push("/dashboard");
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-bold text-slate-900">Masuk</h1>
      <p class="mb-6 mt-1 text-sm text-slate-500">
        Belum punya akun?
        <RouterLink
          to="/register"
          class="font-medium text-blue-600 hover:underline"
          >Daftar</RouterLink
        >
      </p>

      <form @submit.prevent="submit" class="space-y-3">
        <input
          v-model="email"
          type="email"
          placeholder="Email"
          required
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
        />
        <input
          v-model="password"
          type="password"
          placeholder="Password"
          required
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
        />
        <button
          type="submit"
          :disabled="auth.loading"
          class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
        >
          {{ auth.loading ? "Memproses..." : "Masuk" }}
        </button>
      </form>

      <p
        v-if="auth.error"
        class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-600"
      >
        {{ auth.error }}
      </p>
    </div>
  </main>
</template>
