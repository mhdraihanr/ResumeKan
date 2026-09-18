<script setup lang="ts">
import { computed } from "vue";
import FormLabel from "../form/FormLabel.vue";
import { CV_TEMPLATES, CV_FONTS, CV_FONT_SIZES } from "@/lib/cv-templates";

const props = defineProps<{
  title: string;
  template: string;
  language: string;
  titleError?: string;
}>();
defineEmits<{
  "update:title": [v: string];
  "update:template": [v: string];
  "update:language": [v: string];
}>();

const fontFamily = defineModel<string>("fontFamily", { default: "default" });
const fontSize = defineModel<string>("fontSize", { default: "default" });

const selectedTpl = computed(
  () =>
    (CV_TEMPLATES as Record<string, (typeof CV_TEMPLATES)["classic"]>)[
      props.template
    ] ?? CV_TEMPLATES.classic,
);
</script>

<template>
  <section class="space-y-2.5">
    <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500">
      Info CV
    </h2>
    <div class="grid gap-2.5 sm:grid-cols-3">
      <label class="space-y-1">
        <FormLabel label="Judul CV *" />
        <input
          id="cv-title"
          :value="title"
          maxlength="100"
          placeholder="CV Backend"
          :aria-invalid="titleError ? 'true' : undefined"
          :aria-describedby="titleError ? 'cv-title-error' : undefined"
          class="w-full rounded-lg border px-3 py-1.5 text-xs focus:outline-none dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
          :class="
            titleError
              ? 'border-red-500 focus:border-red-500 dark:border-red-500'
              : 'border-slate-300 focus:border-slate-900 dark:border-border'
          "
          @input="
            $emit('update:title', ($event.target as HTMLInputElement).value)
          "
        />
        <p
          v-if="titleError"
          id="cv-title-error"
          class="text-[11px] font-medium text-red-700 dark:text-red-300"
        >
          {{ titleError }}
        </p>
      </label>
      <label class="space-y-1">
        <div class="flex items-center justify-between">
          <FormLabel label="Template" />
          <span
            v-if="selectedTpl.atsFriendly"
            class="inline-flex items-center rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
          >
            ATS Friendly
          </span>
        </div>
        <select
          :value="template"
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
          @change="
            $emit('update:template', ($event.target as HTMLSelectElement).value)
          "
        >
          <option
            v-for="t in Object.values(CV_TEMPLATES)"
            :key="t.id"
            :value="t.id"
          >
            {{ t.label }}
          </option>
        </select>
        <p
          v-if="selectedTpl.atsFriendly"
          class="text-[11px] text-emerald-700 dark:text-emerald-400"
        >
          Format satu kolom standar, direkomendasikan untuk ATS.
        </p>
        <p v-else class="text-[11px] text-slate-500 dark:text-slate-400">
          Format modern dengan aksen visual dan foto profil.
        </p>
      </label>
      <label class="space-y-1">
        <FormLabel label="Bahasa" />
        <select
          :value="language"
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
          @change="
            $emit('update:language', ($event.target as HTMLSelectElement).value)
          "
        >
          <option value="id">Indonesia</option>
          <option value="en">English</option>
        </select>
      </label>
    </div>

    <h2
      class="mt-4 text-sm font-semibold uppercase tracking-widest text-slate-500"
    >
      Tampilan Font
    </h2>
    <div class="grid gap-2.5 sm:grid-cols-2">
      <label class="space-y-1">
        <FormLabel label="Jenis Font" />
        <select
          v-model="fontFamily"
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
        >
          <option v-for="f in CV_FONTS" :key="f.id" :value="f.id">
            {{ f.label }}
          </option>
        </select>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Berlaku untuk tampilan preview dan hasil download PDF.
        </p>
      </label>
      <label class="space-y-1">
        <FormLabel label="Ukuran Font" />
        <select
          v-model="fontSize"
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none dark:border-border dark:bg-secondary-background dark:text-foreground dark:focus:border-ring"
        >
          <option v-for="s in CV_FONT_SIZES" :key="s.id" :value="s.id">
            {{ s.label }}
          </option>
        </select>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Kompak muat lebih banyak konten, Lega lebih santai dibaca.
        </p>
      </label>
    </div>
  </section>
</template>
