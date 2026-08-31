<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";
import FormTextarea from "../form/FormTextarea.vue";

const data = defineModel<CvData>({ required: true });

function addEdu() {
  data.value.education ??= [];
  data.value.education.push({
    institution: "",
    degree: "",
    location: "",
    year: "",
    gpa: "",
    achievements: "",
  });
}
function removeEdu(i: number) {
  data.value.education?.splice(i, 1);
}
</script>

<template>
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Pendidikan
      </h2>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        :disabled="(data.education?.length ?? 0) >= 5"
        @click="addEdu"
      >
        + Tambah
      </button>
    </div>
    <div
      v-for="(edu, i) in data.education"
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
          @click="removeEdu(i)"
        >
          Hapus
        </button>
      </div>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1"
          ><FormLabel label="Institusi *" /><FormInput
            v-model="edu.institution"
            placeholder="Universitas Indonesia"
            required
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Gelar &amp; Jurusan *" /><FormInput
            v-model="edu.degree"
            placeholder="S1 Teknik Informatika / Bachelor of Science in Computer Science"
            required
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Lokasi" /><FormInput
            v-model="edu.location"
            placeholder="Depok, Indonesia"
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Tahun *" /><FormInput
            v-model="edu.year"
            placeholder="2020 - 2024"
            required
        /></label>
      </div>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1"
          ><FormLabel label="IPK" /><FormInput
            v-model="edu.gpa"
            placeholder="3.85/4.00"
            maxlength="10"
        /></label>
      </div>
      <label class="space-y-1 block">
        <FormLabel label="Prestasi / Deskripsi (1 baris = 1 bullet)" />
        <FormTextarea
          v-model="edu.achievements"
          maxlength="1000"
          rows="2"
          placeholder="Cum Laude&#10;Anggota Himpan Mahasiswa Informatika 2021-2023"
        />
      </label>
      <p class="text-right text-xs text-slate-400">
        {{ (edu.achievements ?? "").split("\n").filter(Boolean).length }} bullet
        · {{ (edu.achievements ?? "").length }}/1000
      </p>
    </div>
    <p v-if="!data.education?.length" class="text-xs text-slate-400">
      Belum ada pendidikan. Klik Tambah.
    </p>
  </section>
</template>
