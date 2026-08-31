<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";
import FormTextarea from "../form/FormTextarea.vue";

const data = defineModel<CvData>({ required: true });

function addOrg() {
  data.value.organizations ??= [];
  data.value.organizations.push({
    organization: "",
    role: "",
    period: "",
    description: "",
  });
}
function removeOrg(i: number) {
  data.value.organizations?.splice(i, 1);
}
</script>

<template>
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Organisasi
      </h2>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        :disabled="(data.organizations?.length ?? 0) >= 5"
        @click="addOrg"
      >
        + Tambah
      </button>
    </div>
    <div
      v-for="(org, i) in data.organizations"
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
          @click="removeOrg(i)"
        >
          Hapus
        </button>
      </div>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1"
          ><FormLabel label="Organisasi *" /><FormInput
            v-model="org.organization"
            placeholder="BEM Fasilkom"
            required
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Peran *" /><FormInput
            v-model="org.role"
            placeholder="Ketua Divisi"
            required
        /></label>
        <label class="space-y-1 sm:col-span-2"
          ><FormLabel label="Periode *" /><FormInput
            v-model="org.period"
            placeholder="2022 - 2024"
            required
        /></label>
      </div>
      <label class="space-y-1 block">
        <FormLabel label="Deskripsi (1 baris = 1 bullet)" />
        <FormTextarea
          v-model="org.description"
          maxlength="800"
          rows="2"
          placeholder="Koordinasi 20 anggota, selenggarakan 5 workshop&#10;Kelola anggaran Rp 15jt"
        />
      </label>
      <p class="text-right text-xs text-slate-400">
        {{ (org.description ?? "").split("\n").filter(Boolean).length }} bullet
        · {{ (org.description ?? "").length }}/800
      </p>
    </div>
    <p v-if="!data.organizations?.length" class="text-xs text-slate-400">
      Belum ada organisasi. Klik Tambah (max 5).
    </p>
  </section>
</template>
