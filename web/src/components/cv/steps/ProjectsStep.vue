<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";
import FormTextarea from "../form/FormTextarea.vue";

const data = defineModel<CvData>({ required: true });

function addProject() {
  const p = data.value.projects;
  if (Array.isArray(p))
    p.push({ title: "", role: "", objective: "", techStack: "" });
  else
    data.value.projects = [
      { title: "", role: "", objective: "", techStack: "" },
    ];
}
function removeProject(i: number) {
  data.value.projects?.splice(i, 1);
}
</script>

<template>
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Proyek
      </h2>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        :disabled="(data.projects?.length ?? 0) >= 5"
        @click="addProject"
      >
        + Tambah
      </button>
    </div>
    <div
      v-for="(proj, i) in data.projects"
      :key="i"
      class="rounded-xl border border-slate-200 p-3 space-y-2.5 dark:border-border"
    >
      <div class="flex justify-between">
        <span
          class="text-xs font-semibold text-slate-500 dark:text-foreground/60"
          >#{{ i + 1 }}</span
        >
        <button
          type="button"
          class="text-xs text-red-600 hover:underline dark:text-red-300"
          @click="removeProject(i)"
        >
          Hapus
        </button>
      </div>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1"
          ><FormLabel label="Nama proyek *" /><FormInput
            v-model="proj.title"
            required
            maxlength="100"
            placeholder="ResumeKan"
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Peran *" /><FormInput
            v-model="proj.role"
            required
            maxlength="100"
            placeholder="Fullstack"
        /></label>
      </div>
      <label class="space-y-1 block">
        <FormLabel label="Tujuan" />
        <FormTextarea
          v-model="proj.objective"
          maxlength="500"
          rows="2"
          placeholder="ATS-friendly CV builder, isi form lalu preview dan PDF"
        />
      </label>
      <label class="space-y-1 block">
        <FormLabel label="Tech stack" />
        <FormInput
          v-model="proj.techStack"
          maxlength="200"
          placeholder="Vue, Laravel, PostgreSQL"
        />
      </label>
    </div>
    <p v-if="!data.projects?.length" class="text-xs text-slate-400">
      Belum ada proyek. Klik Tambah (max 5).
    </p>
  </section>
</template>
