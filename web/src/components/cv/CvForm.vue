<script setup lang="ts">
import { ref, watch } from "vue";
import type { CvData } from "@/types/cv";

const props = defineProps<{
  modelValue: CvData;
  title: string;
  template: string;
  language: string;
}>();
const emit = defineEmits<{
  "update:modelValue": [v: CvData];
  "update:title": [v: string];
  "update:template": [v: string];
  "update:language": [v: string];
  submit: [];
}>();

const local = ref<CvData>(JSON.parse(JSON.stringify(props.modelValue)));
watch(
  () => props.modelValue,
  (v) => (local.value = JSON.parse(JSON.stringify(v))),
  { deep: true },
);
watch(local, (v) => emit("update:modelValue", v), { deep: true });

function addExp() {
  local.value.experiences ??= [];
  local.value.experiences.push({
    company: "",
    position: "",
    startDate: "",
    endDate: "",
    description: "",
  });
}
function removeExp(i: number) {
  local.value.experiences?.splice(i, 1);
}
function addEdu() {
  local.value.education ??= [];
  local.value.education.push({ institution: "", degree: "", year: "" });
}
function removeEdu(i: number) {
  local.value.education?.splice(i, 1);
}
function addProject() {
  const p = local.value.projects;
  if (Array.isArray(p))
    p.push({ title: "", role: "", objective: "", techStack: "" });
  else
    local.value.projects = [
      { title: "", role: "", objective: "", techStack: "" },
    ];
}
function removeProject(i: number) {
  (
    local.value.projects as {
      title: string;
      role: string;
      objective?: string;
      techStack?: string;
    }[]
  )?.splice(i, 1);
}
function normalizeProjects() {
  const p = local.value.projects as unknown;
  if (typeof p === "string") {
    const s = (p as string).trim();
    local.value.projects = s
      ? [{ title: s, role: "—", objective: "", techStack: "" }]
      : [];
  }
}
normalizeProjects();
</script>

<template>
  <form @submit.prevent="emit('submit')" class="space-y-8">
    <!-- Meta -->
    <section class="space-y-3">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Info CV
      </h2>
      <div class="grid gap-3 sm:grid-cols-3">
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Judul CV *</span>
          <input
            :value="title"
            @input="
              emit('update:title', ($event.target as HTMLInputElement).value)
            "
            required
            maxlength="100"
            placeholder="CV Backend"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Template</span>
          <select
            :value="template"
            @change="
              emit(
                'update:template',
                ($event.target as HTMLSelectElement).value,
              )
            "
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          >
            <option value="modern">Modern</option>
            <option value="classic">Classic</option>
          </select>
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Bahasa</span>
          <select
            :value="language"
            @change="
              emit(
                'update:language',
                ($event.target as HTMLSelectElement).value,
              )
            "
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          >
            <option value="id">Indonesia</option>
            <option value="en">English</option>
          </select>
        </label>
      </div>
    </section>

    <!-- Personal -->
    <section class="space-y-3">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Data Pribadi
      </h2>
      <div class="grid gap-3 sm:grid-cols-2">
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Nama *</span>
          <input
            v-model="local.personal.name"
            required
            maxlength="100"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Email *</span>
          <input
            v-model="local.personal.email"
            type="email"
            required
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Telepon *</span>
          <input
            v-model="local.personal.phone"
            required
            maxlength="30"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Alamat *</span>
          <input
            v-model="local.personal.address"
            required
            maxlength="200"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">LinkedIn</span>
          <input
            v-model="local.personal.linkedin"
            type="url"
            placeholder="https://linkedin.com/in/..."
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Website</span>
          <input
            v-model="local.personal.website"
            type="url"
            placeholder="https://..."
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
      </div>
    </section>

    <!-- Summary -->
    <section class="space-y-3">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Ringkasan
      </h2>
      <textarea
        v-model="local.summary"
        maxlength="600"
        rows="3"
        placeholder="Ringkasan profesional singkat..."
        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
      ></textarea>
      <p class="text-right text-xs text-slate-400">
        {{ (local.summary ?? "").length }}/600
      </p>
    </section>

    <!-- Experiences -->
    <section class="space-y-3">
      <div class="flex items-center justify-between">
        <h2
          class="text-sm font-semibold uppercase tracking-widest text-slate-500"
        >
          Pengalaman Kerja
        </h2>
        <button
          type="button"
          @click="addExp"
          :disabled="(local.experiences?.length ?? 0) >= 10"
          class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40"
        >
          + Tambah
        </button>
      </div>
      <div
        v-for="(exp, i) in local.experiences"
        :key="i"
        class="rounded-xl border border-slate-200 p-4 space-y-3"
      >
        <div class="flex justify-between">
          <span class="text-xs font-semibold text-slate-500">#{{ i + 1 }}</span>
          <button
            type="button"
            @click="removeExp(i)"
            class="text-xs text-red-600 hover:underline"
          >
            Hapus
          </button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <input
            v-model="exp.company"
            placeholder="Perusahaan *"
            required
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
          <input
            v-model="exp.position"
            placeholder="Posisi *"
            required
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
          <input
            v-model="exp.location"
            placeholder="Lokasi (opsional)"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
          <div class="grid grid-cols-2 gap-3">
            <input
              v-model="exp.startDate"
              placeholder="Mulai (YYYY-MM) *"
              required
              class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
            />
            <input
              v-model="exp.endDate"
              placeholder="Selesai / Present *"
              required
              class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
            />
          </div>
        </div>
        <textarea
          v-model="exp.description"
          maxlength="1500"
          rows="3"
          placeholder="1 baris = 1 bullet ATS. Contoh:&#10;Memimpin migrasi 12 orang, potong backlog 35%&#10;Bangun onboarding React, naikkan aktivasi 18%"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
        ></textarea>
        <p class="text-right text-xs text-slate-400">
          {{
            (exp.description ?? "").split("\n").filter(Boolean).length
          }}
          bullet · {{ (exp.description ?? "").length }}/1500
        </p>
      </div>
      <p v-if="!local.experiences?.length" class="text-xs text-slate-400">
        Belum ada pengalaman. Klik Tambah.
      </p>
    </section>

    <!-- Education -->
    <section class="space-y-3">
      <div class="flex items-center justify-between">
        <h2
          class="text-sm font-semibold uppercase tracking-widest text-slate-500"
        >
          Pendidikan
        </h2>
        <button
          type="button"
          @click="addEdu"
          :disabled="(local.education?.length ?? 0) >= 5"
          class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40"
        >
          + Tambah
        </button>
      </div>
      <div
        v-for="(edu, i) in local.education"
        :key="i"
        class="rounded-xl border border-slate-200 p-4 space-y-3"
      >
        <div class="flex justify-between">
          <span class="text-xs font-semibold text-slate-500">#{{ i + 1 }}</span>
          <button
            type="button"
            @click="removeEdu(i)"
            class="text-xs text-red-600 hover:underline"
          >
            Hapus
          </button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <input
            v-model="edu.institution"
            placeholder="Institusi *"
            required
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
          <input
            v-model="edu.degree"
            placeholder="Gelar *"
            required
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
          <input
            v-model="edu.major"
            placeholder="Jurusan (opsional)"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
          <input
            v-model="edu.year"
            placeholder="Tahun *"
            required
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </div>
        <input
          v-model="edu.achievements"
          placeholder="Prestasi (opsional)"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
        />
      </div>
      <p v-if="!local.education?.length" class="text-xs text-slate-400">
        Belum ada pendidikan. Klik Tambah.
      </p>
    </section>

    <!-- Skills -->
    <section class="space-y-3">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Keahlian
      </h2>
      <div class="grid gap-3 sm:grid-cols-2">
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700"
            >Hard skills (pisah koma)</span
          >
          <input
            v-model="local.skills!.hard"
            maxlength="500"
            placeholder="Go, Laravel, PostgreSQL"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700"
            >Soft skills (pisah koma)</span
          >
          <input
            v-model="local.skills!.soft"
            maxlength="300"
            placeholder="Komunikasi, Leadership"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
      </div>
    </section>

    <!-- Lainnya -->
    <section class="space-y-3">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Lainnya
      </h2>
      <label class="space-y-1 block">
        <span class="text-xs font-medium text-slate-700">Bahasa</span>
        <input
          v-model="local.languages"
          maxlength="200"
          placeholder="Indonesia (Native), English (Fluent)"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
        />
      </label>
      <label class="space-y-1 block">
        <span class="text-xs font-medium text-slate-700">Sertifikat</span>
        <textarea
          v-model="local.certificates"
          maxlength="1000"
          rows="2"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
        ></textarea>
      </label>
    </section>

    <!-- Proyek -->
    <section class="space-y-3">
      <div class="flex items-center justify-between">
        <h2
          class="text-sm font-semibold uppercase tracking-widest text-slate-500"
        >
          Proyek
        </h2>
        <button
          type="button"
          @click="addProject"
          :disabled="
            ((local.projects as unknown as unknown[])?.length ?? 0) >= 5
          "
          class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40"
        >
          + Tambah
        </button>
      </div>
      <div
        v-for="(proj, i) in local.projects as {
          title: string;
          role: string;
          objective?: string;
          techStack?: string;
        }[]"
        :key="i"
        class="rounded-xl border border-slate-200 p-4 space-y-3"
      >
        <div class="flex justify-between">
          <span class="text-xs font-semibold text-slate-500">#{{ i + 1 }}</span>
          <button
            type="button"
            @click="removeProject(i)"
            class="text-xs text-red-600 hover:underline"
          >
            Hapus
          </button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700"
              >Nama proyek *</span
            >
            <input
              v-model="proj.title"
              required
              maxlength="100"
              placeholder="ResumeKan"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Peran *</span>
            <input
              v-model="proj.role"
              required
              maxlength="100"
              placeholder="Fullstack"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
            />
          </label>
        </div>
        <label class="space-y-1 block">
          <span class="text-xs font-medium text-slate-700">Tujuan</span>
          <textarea
            v-model="proj.objective"
            maxlength="500"
            rows="2"
            placeholder="ATS-friendly CV builder — isi form → preview → PDF"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          ></textarea>
        </label>
        <label class="space-y-1 block">
          <span class="text-xs font-medium text-slate-700">Tech stack</span>
          <input
            v-model="proj.techStack"
            maxlength="200"
            placeholder="Vue, Laravel, PostgreSQL"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
          />
        </label>
      </div>
      <p
        v-if="!(local.projects as unknown as unknown[])?.length"
        class="text-xs text-slate-400"
      >
        Belum ada proyek. Klik Tambah (max 5).
      </p>
    </section>

    <button
      type="submit"
      class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
    >
      Simpan CV
    </button>
  </form>
</template>
