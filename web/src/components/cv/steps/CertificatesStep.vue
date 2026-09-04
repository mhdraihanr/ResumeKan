<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";

const data = defineModel<CvData>({ required: true });

function addCert() {
  const c = data.value.certificates;
  if (Array.isArray(c))
    c.push({ name: "", issuer: "", year: "", credentialId: "" });
  else
    data.value.certificates = [
      { name: "", issuer: "", year: "", credentialId: "" },
    ];
}
function removeCert(i: number) {
  data.value.certificates?.splice(i, 1);
}
</script>

<template>
  <section class="space-y-2.5">
    <div class="flex items-center justify-between">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Sertifikat
      </h2>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
        :disabled="(data.certificates?.length ?? 0) >= 5"
        @click="addCert"
      >
        + Tambah
      </button>
    </div>
    <div
      v-for="(cert, i) in data.certificates"
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
          @click="removeCert(i)"
        >
          Hapus
        </button>
      </div>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1"
          ><FormLabel label="Nama sertifikat *" /><FormInput
            v-model="cert.name"
            required
            maxlength="100"
            placeholder="AWS Solutions Architect"
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Penerbit *" /><FormInput
            v-model="cert.issuer"
            required
            maxlength="100"
            placeholder="Amazon Web Services"
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Tahun terbit *" /><FormInput
            v-model="cert.year"
            required
            maxlength="10"
            placeholder="2024"
        /></label>
        <label class="space-y-1"
          ><FormLabel label="Credential ID (opsional)" /><FormInput
            v-model="cert.credentialId"
            maxlength="100"
            placeholder="COMP001234567"
        /></label>
      </div>
    </div>
    <p v-if="!data.certificates?.length" class="text-xs text-slate-400">
      Belum ada sertifikat. Klik Tambah (max 5).
    </p>
  </section>
</template>
