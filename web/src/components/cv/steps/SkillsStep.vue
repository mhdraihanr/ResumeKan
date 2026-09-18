<script setup lang="ts">
import { defaultSkillGroups, type CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";
import FormTextarea from "../form/FormTextarea.vue";

const data = defineModel<CvData>({ required: true });

/** Peta path field -> pesan error inline, mis. `skills.2.label`. */
const props = defineProps<{ errors?: Record<string, string> }>();

/** Maksimal 5 grup total (2 bawaan + grup kustom). */
const MAX_GROUPS = 5;

function err(i: number, field: string): string | undefined {
  return props.errors?.[`skills.${i}.${field}`];
}

/**
 * Dua grup bawaan (Hard/Soft) selalu menempati dua posisi pertama. Kalau data
 * lama belum berbentuk array, atau urutannya bergeser, form menormalkannya
 * dulu supaya `data.skills![0]`/`[1]` di template tidak pernah undefined.
 */
function ensureDefaults(): void {
  if (!Array.isArray(data.value.skills)) {
    data.value.skills = defaultSkillGroups();
    return;
  }
  const list = data.value.skills;
  if (list.length < 2) {
    data.value.skills = defaultSkillGroups();
  }
}
ensureDefaults();

/** Grup kustom = semua grup setelah 2 grup bawaan. */
function customGroups() {
  return (data.value.skills ?? []).slice(2);
}

function addGroup() {
  const list = data.value.skills;
  if (!Array.isArray(list)) {
    data.value.skills = defaultSkillGroups();
    return;
  }
  if (list.length >= MAX_GROUPS) return;
  list.push({ label: "", items: "" });
}

function removeGroup(index: number) {
  // `index` relatif terhadap daftar kustom (0 = grup kustom pertama).
  data.value.skills?.splice(index + 2, 1);
}
</script>

<template>
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-300"
      >
        Keahlian
      </h2>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        :disabled="(data.skills?.length ?? 0) >= MAX_GROUPS"
        @click="addGroup"
      >
        + Tambah grup
      </button>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-300">
      Pisah tiap keahlian dengan koma. Tambah grup kalau mau memisahkan kategori
      (mis. Library &amp; Frameworks, Tools, Database).
    </p>

    <label class="space-y-1 block">
      <FormLabel label="Hard skills (pisah koma)" />
      <FormTextarea
        v-model="data.skills![0]!.items"
        maxlength="500"
        rows="2"
        placeholder="Go, Laravel, PostgreSQL, Docker, Redis, REST API, Git, CI/CD"
        :error="err(0, 'items')"
      />
    </label>
    <label class="space-y-1 block">
      <FormLabel label="Soft skills (pisah koma)" />
      <FormTextarea
        v-model="data.skills![1]!.items"
        maxlength="500"
        rows="2"
        placeholder="Komunikasi, Leadership, Stakeholder Management, Problem Solving"
        :error="err(1, 'items')"
      />
    </label>

    <div
      v-for="(group, i) in customGroups()"
      :key="i + 2"
      class="rounded-xl border border-slate-200 p-3 space-y-2.5 dark:border-border"
    >
      <div class="flex justify-between">
        <span
          class="text-xs font-semibold text-slate-500 dark:text-foreground/75"
          >Grup kustom #{{ i + 1 }}</span
        >
        <button
          type="button"
          class="text-xs text-red-600 hover:underline dark:text-red-300"
          @click="removeGroup(i)"
        >
          Hapus
        </button>
      </div>
      <label class="space-y-1 block">
        <FormLabel label="Nama grup *" />
        <FormInput
          :id="`skill-${i + 2}-label`"
          v-model="group.label"
          maxlength="40"
          placeholder="Library & Frameworks"
          :error="err(i + 2, 'label')"
        />
      </label>
      <label class="space-y-1 block">
        <FormLabel label="Keahlian (pisah koma)" />
        <FormTextarea
          v-model="group.items"
          maxlength="500"
          rows="2"
          placeholder="Vue 3, React, Laravel, Tailwind CSS"
          :error="err(i + 2, 'items')"
        />
      </label>
    </div>

    <p
      v-if="!customGroups().length"
      class="text-xs text-slate-500 dark:text-slate-300"
    >
      Belum ada grup kustom. Klik Tambah grup (max 5 total). Idealnya 2-5
      kategori, 3-6 keahlian per kategori.
    </p>
  </section>
</template>
