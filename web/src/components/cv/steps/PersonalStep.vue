<script setup lang="ts">
import type { CvData } from "@/types/cv";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";

const data = defineModel<CvData>({ required: true });
</script>

<template>
  <section class="space-y-2.5">
    <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500">
      Data Pribadi
    </h2>
    <div class="grid gap-2.5 sm:grid-cols-2">
      <label class="space-y-1">
        <FormLabel label="Nama *" />
        <FormInput
          v-model="data.personal.name"
          required
          maxlength="100"
          placeholder="Budi Santoso"
        />
      </label>
      <label class="space-y-1">
        <FormLabel label="Email *" />
        <FormInput
          v-model="data.personal.email"
          type="email"
          required
          placeholder="budi@email.com"
        />
      </label>
      <label class="space-y-1">
        <FormLabel label="Telepon *" />
        <FormInput
          v-model="data.personal.phone"
          required
          maxlength="30"
          placeholder="+62 812-3456-7890"
        />
      </label>
      <label class="space-y-1">
        <FormLabel label="Alamat *" />
        <FormInput
          v-model="data.personal.address"
          required
          maxlength="200"
          placeholder="Jakarta, Indonesia"
        />
      </label>
      <label class="space-y-1">
        <FormLabel label="LinkedIn" />
        <FormInput
          v-model="data.personal.linkedin"
          placeholder="linkedin.com/in/nama atau www.linkedin.com/in/nama"
        />
      </label>
      <label class="space-y-1">
        <FormLabel label="Website" />
        <FormInput
          v-model="data.personal.website"
          placeholder="www.example.com atau https://example.com"
        />
      </label>
      <label class="space-y-1">
        <FormLabel label="GitHub" />
        <FormInput
          v-model="data.personal.github"
          placeholder="github.com/username atau www.github.com/username"
        />
      </label>
    </div>
  </section>
</template>
