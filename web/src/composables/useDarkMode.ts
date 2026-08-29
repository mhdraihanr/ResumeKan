import { ref, watch } from "vue";

// 3-way: "auto" (ikuti OS, default), "light", "dark".
// Referensi: Tailwind docs dark mode + VueUse useDark/useColorMode.
export type ThemeChoice = "auto" | "light" | "dark";

const STORAGE_KEY = "resumekan-theme";

const choice = ref<ThemeChoice>("auto");
const systemDark = ref(false);

function readStored(): ThemeChoice {
  try {
    const t = localStorage.getItem(STORAGE_KEY);
    return t === "light" || t === "dark" ? t : "auto";
  } catch {
    return "auto";
  }
}

function resolveIsDark(): boolean {
  return choice.value === "auto" ? systemDark.value : choice.value === "dark";
}

function apply() {
  document.documentElement.classList.toggle("dark", resolveIsDark());
  // color-scheme bikin scrollbar & form native ikut mode (superdesign.dev token sheet)
  document.documentElement.style.colorScheme = resolveIsDark() ? "dark" : "light";
}

function initTheme() {
  choice.value = readStored();
  systemDark.value = window.matchMedia("(prefers-color-scheme: dark)").matches;
  apply();
  // Ikuti perubahan OS saat mode auto (VueUse usePreferredDark pattern)
  window
    .matchMedia("(prefers-color-scheme: dark)")
    .addEventListener("change", (e) => {
      systemDark.value = e.matches;
      apply();
    });
}

// Siklus: auto -> light -> dark -> auto
function cycle() {
  choice.value =
    choice.value === "auto"
      ? "light"
      : choice.value === "light"
        ? "dark"
        : "auto";
  try {
    if (choice.value === "auto") localStorage.removeItem(STORAGE_KEY);
    else localStorage.setItem(STORAGE_KEY, choice.value);
  } catch {
    /* storage penuh/blokir: mode tetap berlaku sesi ini */
  }
  apply();
}

function set(next: ThemeChoice) {
  choice.value = next;
  try {
    if (next === "auto") localStorage.removeItem(STORAGE_KEY);
    else localStorage.setItem(STORAGE_KEY, next);
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
