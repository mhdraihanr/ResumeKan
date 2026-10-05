import { defineStore } from "pinia";
import { ref, computed } from "vue";

export interface User {
  id: number;
  name: string;
  email: string;
  /** Timestamp ISO dari kolom `users.created_at` (dipakai di dashboard). */
  created_at?: string;
}

/** Bukti anti-spam yang dirakit form register (lihat VerifyTurnstile di API). */
export interface RegisterSpam {
  /** Token Turnstile dari widget (`cf-turnstile-response`). */
  turnstileToken: string;
  /** Nilai field umpan; user asli selalu mengirim string kosong. */
  honeypot: string;
  /** Cap waktu terenkripsi dari `GET /config` (anti submit instan). */
  spamToken: string;
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
    spam: RegisterSpam,
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
          // Honeypot dikirim apa adanya; backend menolak kalau terisi.
          website: spam.honeypot,
          spam_token: spam.spamToken,
          "cf-turnstile-response": spam.turnstileToken,
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

  /**
   * Logout optimistik: state lokal dibersihkan segera supaya UI terasa instan,
   * lalu permintaan ke server dikirim di background (tidak ditunggu).
   * Kegagalan request diabaikan — sesi lokal sudah berakhir dan cookie akan
   * tetap hangus saat server merespons berikutnya.
   */
  function logout() {
    user.value = null;
    error.value = null;

    void (async () => {
      try {
        await fetchCsrf();
        await fetch("/api/v1/logout", {
          method: "POST",
          headers: { Accept: "application/json" },
          credentials: "include",
        });
      } catch {
        // Diabaikan: user sudah dianggap keluar di sisi klien.
      }
    })();
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
