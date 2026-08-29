import { ref, watch } from "vue";

const isDark = ref(false);

function initTheme() {
  const stored = localStorage.getItem("resumekan-theme");
  if (stored === "dark") {
    isDark.value = true;
  } else if (stored === "light") {
    isDark.value = false;
  } else {
    isDark.value = window.matchMedia("(prefers-color-scheme: dark)").matches;
  }
  apply();
}

function apply() {
  document.documentElement.classList.toggle("dark", isDark.value);
}

function toggle() {
  isDark.value = !isDark.value;
  localStorage.setItem("resumekan-theme", isDark.value ? "dark" : "light");
  apply();
}

watch(isDark, apply);

export function useDarkMode() {
  return { isDark, toggle, initTheme };
}