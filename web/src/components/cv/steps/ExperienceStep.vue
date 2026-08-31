<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";
import FormSelect from "../form/FormSelect.vue";
import FormTextarea from "../form/FormTextarea.vue";

const data = defineModel<CvData>({ required: true });

function addExp() {
  data.value.experiences ??= [];
  data.value.experiences.push({
    company: "",
    position: "",
    location: "",
    employmentType: "",
    startDate: "",
    endDate: "",
    description: "",
  });
}
function removeExp(i: number) {
  data.value.experiences?.splice(i, 1);
}
</script>

<template>
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Pengalaman Kerja
      </h2>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        :disabled="(data.experiences?.length ?? 0) >= 10"
        @click="addExp"
      >
        + Tambah
      </button>
    </div>
    <div
      v-for="(exp, i) in data.experiences"
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
          @click="removeExp(i)"
        >
          Hapus
        </button>
      </div>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1"
          ><FormLabel label="Perusahaan *" /><FormInput
            v-model="exp.company"
            placeholder="PT Maju Jaya"
            required
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Posisi *" /><FormInput
            v-model="exp.position"
            placeholder="Backend Engineer"
            required
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Lokasi" /><FormInput
            v-model="exp.location"
            placeholder="Jakarta, Indonesia"
        /></label>
        <label class="space-y-1">
          <FormLabel label="Employment Type" />
          <FormSelect v-model="exp.employmentType">
            <option value="">Pilih</option>
            <option value="Full-time">Full-time</option>
            <option value="Part-time">Part-time</option>
            <option value="Internship">Internship</option>
            <option value="Contract">Contract</option>
            <option value="Freelance">Freelance</option>
          </FormSelect>
        </label>
        <div class="grid grid-cols-2 gap-2.5">
          <label class="space-y-1"
            ><FormLabel label="Mulai *" /><FormInput
              v-model="exp.startDate"
              placeholder="2022-01"
              required
          /></label>
          <label class="space-y-1"
            ><FormLabel label="Selesai *" /><FormInput
              v-model="exp.endDate"
              placeholder="2024-12 / Present"
              required
          /></label>
        </div>
      </div>
      <label class="space-y-1 block">
        <FormLabel label="Deskripsi (1 baris = 1 bullet)" />
        <FormTextarea
          v-model="exp.description"
          maxlength="1500"
          rows="3"
          placeholder="Memimpin migrasi 12 orang, potong backlog 35%&#10;Bangun onboarding React, naikkan aktivasi 18%"
        />
      </label>
      <p class="text-right text-xs text-slate-400">
        {{ (exp.description ?? "").split("\n").filter(Boolean).length }} bullet
        · {{ (exp.description ?? "").length }}/1500
      </p>
    </div>
    <p v-if="!data.experiences?.length" class="text-xs text-slate-400">
      Belum ada pengalaman. Klik Tambah.
    </p>
  </section>
</template>
