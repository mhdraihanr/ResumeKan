import { defineStore } from "pinia";
import { ref, computed } from "vue";

export interface User {
  id: number;
  name: string;
  email: string;
}

export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const isAuthenticated = computed(() => user.value !== null);

  async function fetchCsrf() {
    await fetch("/sanctum/csrf-cookie", { credentials: "include" });
  }

  async function register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ) {
    loading.value = true;
    error.value = null;
    try {
      await fetchCsrf();
      const res = await fetch("/api/v1/register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
        }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message || "Register gagal");
      user.value = json.user;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Terjadi kesalahan";
    } finally {
      loading.value = false;
    }
  }

  async function login(email: string, password: string) {
    loading.value = true;
    error.value = null;
    try {
      await fetchCsrf();
      const res = await fetch("/api/v1/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        credentials: "include",
        body: JSON.stringify({ email, password }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message || "Login gagal");
      user.value = json.user;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Terjadi kesalahan";
    } finally {
      loading.value = false;
    }
  }

  async function fetchUser() {
    const res = await fetch("/api/v1/user", {
      headers: { Accept: "application/json" },
      credentials: "include",
    });
    if (res.ok) user.value = await res.json();
    else user.value = null;
  }

  async function logout() {
    loading.value = true;
    try {
      await fetchCsrf();
      await fetch("/api/v1/logout", {
        method: "POST",
        headers: { Accept: "application/json" },
        credentials: "include",
      });
    } finally {
      user.value = null;
      loading.value = false;
    }
  }

  return {
    user,
    loading,
    error,
    isAuthenticated,
    register,
    login,
    fetchUser,
    logout,
  };
});
