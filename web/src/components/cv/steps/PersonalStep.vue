<script setup lang="ts">
import { ref } from "vue";
import type { CvData } from "@/types/cv";
import { uploadPhoto } from "@/lib/cloudinary";
import FormLabel from "../form/FormLabel.vue";
import FormInput from "../form/FormInput.vue";

const data = defineModel<CvData>({ required: true });

const uploading = ref(false);
const uploadError = ref("");

async function onPhotoChange(e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  uploadError.value = "";
  uploading.value = true;
  try {
    const url = await uploadPhoto(file);
    data.value.personal.photo = url;
  } catch (err) {
    uploadError.value =
      err instanceof Error ? err.message : "Upload foto gagal.";
  } finally {
    uploading.value = false;
    input.value = "";
  }
}

function clearPhoto() {
  data.value.personal.photo = "";
  uploadError.value = "";
}
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
      <label class="space-y-1 sm:col-span-2">
        <FormLabel label="Foto (opsional, untuk template Neon)" />
        <div v-if="!data.personal.photo" class="space-y-1">
          <input
            type="file"
            accept="image/*"
            :disabled="uploading"
            @change="onPhotoChange"
            class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-base file:border-2 file:border-ink file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-900 hover:file:bg-slate-100"
          />
          <p v-if="uploading" class="text-[11px] text-slate-500">
            Mengunggah foto...
          </p>
          <p v-if="uploadError" class="text-[11px] text-red-600">
            {{ uploadError }}
          </p>
        </div>
        <div v-else class="flex items-center gap-3">
          <img
            :src="data.personal.photo"
            alt="Foto profil"
            class="h-14 w-14 rounded object-cover"
          />
          <div class="text-[11px] text-slate-500">
            <p class="mb-1 break-all">{{ data.personal.photo }}</p>
            <button
              type="button"
              @click="clearPhoto"
              class="text-red-600 underline underline-offset-2 hover:text-red-700"
            >
              Hapus foto
            </button>
          </div>
        </div>
        <p v-if="!data.personal.photo" class="text-[11px] text-slate-500">
          Hanya dipakai template Neon. Kosong = tanpa foto. Maks 2 MB.
        </p>
      </label>
    </div>
  </section>
</template>
