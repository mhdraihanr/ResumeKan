import { ref, watch } from "vue";

// 2-way: "light" (default) | "dark". Tanpa auto/device.
// Referensi: Lea Verou 2026 two states are enough + Tailwind docs.
export type ThemeChoice = "light" | "dark";

const STORAGE_KEY = "resumekan-theme";

const choice = ref<ThemeChoice>("light");

function readStored(): ThemeChoice {
  try {
    const t = localStorage.getItem(STORAGE_KEY);
    return t === "dark" ? "dark" : "light";
  } catch {
    return "light";
  }
}

function resolveIsDark(): boolean {
  return choice.value === "dark";
}

function apply() {
  document.documentElement.classList.toggle("dark", resolveIsDark());
  document.documentElement.style.colorScheme = resolveIsDark()
    ? "dark"
    : "light";
}

function initTheme() {
  choice.value = readStored();
  apply();
}

function cycle() {
  choice.value = choice.value === "light" ? "dark" : "light";
  try {
    localStorage.setItem(STORAGE_KEY, choice.value);
  } catch {
    /* storage penuh/blokir: mode tetap berlaku sesi ini */
  }
  apply();
}

function set(next: ThemeChoice) {
  choice.value = next;
  try {
    localStorage.setItem(STORAGE_KEY, next);
  } catch {
    /* abaikan */
  }
  apply();
}

const isDark = () => resolveIsDark();

watch(choice, apply);

export function useDarkMode() {
  return { choice, isDark, cycle, set, initTheme };
}
