<script setup lang="ts">
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const router = useRouter();

async function handleLogout() {
  await auth.logout();
  router.push("/login");
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-sm">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <button
          @click="handleLogout"
          class="rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-300"
        >
          Logout
        </button>
      </div>

      <p class="text-sm text-slate-600">
        Halo,
        <span class="font-semibold text-slate-900">{{ auth.user?.name }}</span>
        👋
      </p>
      <p class="mt-1 text-xs text-slate-400">{{ auth.user?.email }}</p>

      <div
        class="mt-6 rounded-lg border border-dashed border-slate-300 p-8 text-center"
      >
        <p class="text-sm text-slate-500">Belum ada CV.</p>
        <p class="mt-1 text-xs text-slate-400">CRUD CV hadir di Fase 2.</p>
      </div>
    </div>
  </main>
</template>
