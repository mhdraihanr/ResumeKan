<script setup lang="ts">
import { ref, watch } from "vue";
import type { CvData } from "@/types/cv";
import { cvApi } from "@/api/cv";

const props = defineProps<{
  modelValue: CvData;
  title: string;
  template: string;
  language: string;
  cvId?: number;
}>();
const emit = defineEmits<{
  "update:modelValue": [v: CvData];
  "update:title": [v: string];
  "update:template": [v: string];
  "update:language": [v: string];
  submit: [];
}>();

const local = ref<CvData>(JSON.parse(JSON.stringify(props.modelValue)));
let syncing = false;
watch(
  () => props.modelValue,
  (v) => {
    if (syncing) return;
    syncing = true;
    local.value = JSON.parse(JSON.stringify(v));
    syncing = false;
  },
  { deep: true },
);
watch(
  local,
  (v) => {
    if (syncing) return;
    syncing = true;
    emit("update:modelValue", JSON.parse(JSON.stringify(v)));
    // reset next tick agar tidak lock
    setTimeout(() => (syncing = false), 0);
  },
  { deep: true },
);

function addExp() {
  local.value.experiences ??= [];
  local.value.experiences.push({
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
  local.value.experiences?.splice(i, 1);
}
function addEdu() {
  local.value.education ??= [];
  local.value.education.push({
    institution: "",
    degree: "",
    location: "",
    year: "",
    gpa: "",
    achievements: "",
  });
}
function removeEdu(i: number) {
  local.value.education?.splice(i, 1);
}
function addOrg() {
  local.value.organizations ??= [];
  local.value.organizations.push({
    organization: "",
    role: "",
    period: "",
    description: "",
  });
}
function removeOrg(i: number) {
  local.value.organizations?.splice(i, 1);
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
      ? [{ title: s, role: "", objective: "", techStack: "" }]
      : [];
  }
}
normalizeProjects();

function autoResize(e: Event) {
  const el = e.target as HTMLTextAreaElement;
  if (CSS.supports("field-sizing", "content")) return;
  el.style.height = "auto";
  el.style.height = el.scrollHeight + "px";
}

const aiLoading = ref(false);
const aiError = ref("");

const activeStep = ref(0);
const steps = [
  { label: "Info", key: "meta" },
  { label: "Pribadi", key: "personal" },
  { label: "Ringkasan", key: "summary" },
  { label: "Pengalaman", key: "experience" },
  { label: "Pendidikan", key: "education" },
  { label: "Organisasi", key: "organization" },
  { label: "Keahlian", key: "skills" },
  { label: "Proyek", key: "projects" },
  { label: "Lainnya", key: "other" },
];
async function generateSummary() {
  if (!props.cvId) {
    aiError.value = "Simpan CV dulu sebelum generate.";
    return;
  }
  aiLoading.value = true;
  aiError.value = "";
  try {
    const res = await cvApi.aiSummary(props.cvId, props.language, local.value);
    local.value.summary = res.summary;
  } catch (e: unknown) {
    const err = e as { status?: number; message?: string };
    if (err.status === 429)
      aiError.value = "Terlalu sering, coba lagi 1 menit.";
    else if (err.status === 502 || err.status === 503)
      aiError.value = err.message || "AI tidak tersedia.";
    else aiError.value = err.message || "Gagal generate.";
  } finally {
    aiLoading.value = false;
  }
}
</script>

<template>
  <form @submit.prevent="emit('submit')" class="space-y-6">
    <!-- Stepper nav -->
    <nav
      class="flex flex-wrap gap-1.5 border-b border-slate-200 pb-3 dark:border-border"
    >
      <button
        v-for="(step, i) in steps"
        :key="step.key"
        type="button"
        @click="activeStep = i"
        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition"
        :class="
          i === activeStep
            ? 'bg-slate-900 text-white dark:bg-main'
            : i < activeStep
              ? 'text-slate-700 hover:bg-slate-100 dark:text-foreground/70 dark:hover:bg-ink/20'
              : 'text-slate-400 hover:bg-slate-50 dark:text-foreground/40 dark:hover:bg-ink/10'
        "
      >
        <span
          class="size-4 rounded-full text-center text-[10px] leading-4"
          :class="
            i < activeStep
              ? 'bg-emerald-500 text-white'
              : i === activeStep
                ? 'bg-white/20'
                : 'bg-slate-200 dark:bg-ink/30'
          "
          >{{ i < activeStep ? "✓" : i + 1 }}</span
        >
        {{ step.label }}
      </button>
    </nav>

    <!-- Meta -->
    <section v-show="activeStep === 0" class="space-y-2.5">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Info CV
      </h2>
      <div class="grid gap-2.5 sm:grid-cols-3">
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
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
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
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
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
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
          >
            <option value="id">Indonesia</option>
            <option value="en">English</option>
          </select>
        </label>
      </div>
    </section>

    <!-- Personal -->
    <section v-show="activeStep === 1" class="space-y-2.5">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Data Pribadi
      </h2>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Nama *</span>
          <input
            v-model="local.personal.name"
            required
            maxlength="100"
            placeholder="Budi Santoso"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Email *</span>
          <input
            v-model="local.personal.email"
            type="email"
            required
            placeholder="budi@email.com"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Telepon *</span>
          <input
            v-model="local.personal.phone"
            required
            maxlength="30"
            placeholder="+62 812-3456-7890"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Alamat *</span>
          <input
            v-model="local.personal.address"
            required
            maxlength="200"
            placeholder="Jakarta, Indonesia"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">LinkedIn</span>
          <input
            v-model="local.personal.linkedin"
            type="text"
            placeholder="linkedin.com/in/nama atau www.linkedin.com/in/nama"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">Website</span>
          <input
            v-model="local.personal.website"
            type="text"
            placeholder="www.example.com atau https://example.com"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
          />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700">GitHub</span>
          <input
            v-model="local.personal.github"
            type="text"
            placeholder="github.com/username atau www.github.com/username"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
          />
        </label>
      </div>
    </section>

    <!-- Summary -->
    <section v-show="activeStep === 2" class="space-y-2.5">
      <div class="flex items-center justify-between">
        <h2
          class="text-sm font-semibold uppercase tracking-widest text-slate-500"
        >
          Ringkasan
        </h2>
        <button
          v-if="cvId"
          type="button"
          @click="generateSummary"
          :disabled="aiLoading"
          class="rounded-lg bg-slate-900 px-3 py-1 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-40 dark:bg-main dark:hover:bg-blue-500"
        >
          {{ aiLoading ? "Memproses..." : "Generate AI" }}
        </button>
      </div>
      <p v-if="aiError" class="text-xs text-red-600 dark:text-red-400">
        {{ aiError }}
      </p>
      <textarea
        v-model="local.summary"
        maxlength="600"
        rows="3"
        placeholder="Ringkasan profesional singkat... (klik Generate AI jika CV sudah tersimpan)"
        class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
        @input="autoResize($event)"
      ></textarea>
      <p class="text-right text-xs text-slate-400">
        {{ (local.summary ?? "").length }}/600
      </p>
    </section>

    <!-- Experiences -->
    <section v-show="activeStep === 3" class="space-y-2.5">
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
          class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-ink/20"
        >
          + Tambah
        </button>
      </div>
      <div
        v-for="(exp, i) in local.experiences"
        :key="i"
        class="rounded-xl border border-slate-200 p-3 space-y-2.5 dark:border-border"
      >
        <div class="flex justify-between">
          <span
            class="text-xs font-semibold text-slate-500 dark:text-foreground/50"
            >#{{ i + 1 }}</span
          >
          <button
            type="button"
            @click="removeExp(i)"
            class="text-xs text-red-600 hover:underline dark:text-red-400"
          >
            Hapus
          </button>
        </div>
        <div class="grid gap-2.5 sm:grid-cols-2">
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Perusahaan *</span>
            <input
              v-model="exp.company"
              placeholder="PT Maju Jaya"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Posisi *</span>
            <input
              v-model="exp.position"
              placeholder="Backend Engineer"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Lokasi</span>
            <input
              v-model="exp.location"
              placeholder="Jakarta, Indonesia"
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700"
              >Employment Type</span
            >
            <select
              v-model="exp.employmentType"
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            >
              <option value="">Pilih</option>
              <option value="Full-time">Full-time</option>
              <option value="Part-time">Part-time</option>
              <option value="Internship">Internship</option>
              <option value="Contract">Contract</option>
              <option value="Freelance">Freelance</option>
            </select>
          </label>
          <div class="grid grid-cols-2 gap-2.5">
            <label class="space-y-1">
              <span class="text-xs font-medium text-slate-700">Mulai *</span>
              <input
                v-model="exp.startDate"
                placeholder="2022-01"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
              />
            </label>
            <label class="space-y-1">
              <span class="text-xs font-medium text-slate-700">Selesai *</span>
              <input
                v-model="exp.endDate"
                placeholder="2024-12 / Present"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
              />
            </label>
          </div>
        </div>
        <label class="space-y-1 block">
          <span class="text-xs font-medium text-slate-700"
            >Deskripsi (1 baris = 1 bullet)</span
          >
          <textarea
            v-model="exp.description"
            maxlength="1500"
            rows="3"
            placeholder="Memimpin migrasi 12 orang, potong backlog 35%&#10;Bangun onboarding React, naikkan aktivasi 18%"
            class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            @input="autoResize($event)"
          ></textarea>
        </label>
        <p class="text-right text-xs text-slate-400">
          {{ (exp.description ?? "").split("\n").filter(Boolean).length }}
          bullet · {{ (exp.description ?? "").length }}/1500
        </p>
      </div>
      <p v-if="!local.experiences?.length" class="text-xs text-slate-400">
        Belum ada pengalaman. Klik Tambah.
      </p>
    </section>

    <!-- Education -->
    <section v-show="activeStep === 4" class="space-y-2.5">
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
          class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-ink/20"
        >
          + Tambah
        </button>
      </div>
      <div
        v-for="(edu, i) in local.education"
        :key="i"
        class="rounded-xl border border-slate-200 p-3 space-y-2.5 dark:border-border"
      >
        <div class="flex justify-between">
          <span
            class="text-xs font-semibold text-slate-500 dark:text-foreground/50"
            >#{{ i + 1 }}</span
          >
          <button
            type="button"
            @click="removeEdu(i)"
            class="text-xs text-red-600 hover:underline dark:text-red-400"
          >
            Hapus
          </button>
        </div>
        <div class="grid gap-2.5 sm:grid-cols-2">
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Institusi *</span>
            <input
              v-model="edu.institution"
              placeholder="Universitas Indonesia"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700"
              >Gelar &amp; Jurusan *</span
            >
            <input
              v-model="edu.degree"
              placeholder="S1 Teknik Informatika / Bachelor of Science in Computer Science"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Lokasi</span>
            <input
              v-model="edu.location"
              placeholder="Depok, Indonesia"
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Tahun *</span>
            <input
              v-model="edu.year"
              placeholder="2020 - 2024"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
        </div>
        <div class="grid gap-2.5 sm:grid-cols-2">
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">IPK</span>
            <input
              v-model="edu.gpa"
              placeholder="3.85/4.00"
              maxlength="10"
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
        </div>
        <label class="space-y-1 block">
          <span class="text-xs font-medium text-slate-700"
            >Prestasi / Deskripsi (1 baris = 1 bullet)</span
          >
          <textarea
            v-model="edu.achievements"
            maxlength="1000"
            rows="2"
            placeholder="Cum Laude&#10;Anggota Himpan Mahasiswa Informatika 2021-2023"
            class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            @input="autoResize($event)"
          ></textarea>
        </label>
        <p class="text-right text-xs text-slate-400">
          {{ (edu.achievements ?? "").split("\n").filter(Boolean).length }}
          bullet · {{ (edu.achievements ?? "").length }}/1000
        </p>
      </div>
      <p v-if="!local.education?.length" class="text-xs text-slate-400">
        Belum ada pendidikan. Klik Tambah.
      </p>
    </section>

    <!-- Organisasi -->
    <section v-show="activeStep === 5" class="space-y-2.5">
      <div class="flex items-center justify-between">
        <h2
          class="text-sm font-semibold uppercase tracking-widest text-slate-500"
        >
          Organisasi
        </h2>
        <button
          type="button"
          @click="addOrg"
          :disabled="(local.organizations?.length ?? 0) >= 5"
          class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-ink/20"
        >
          + Tambah
        </button>
      </div>
      <div
        v-for="(org, i) in local.organizations"
        :key="i"
        class="rounded-xl border border-slate-200 p-3 space-y-2.5 dark:border-border"
      >
        <div class="flex justify-between">
          <span
            class="text-xs font-semibold text-slate-500 dark:text-foreground/50"
            >#{{ i + 1 }}</span
          >
          <button
            type="button"
            @click="removeOrg(i)"
            class="text-xs text-red-600 hover:underline dark:text-red-400"
          >
            Hapus
          </button>
        </div>
        <div class="grid gap-2.5 sm:grid-cols-2">
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Organisasi *</span>
            <input
              v-model="org.organization"
              placeholder="BEM Fasilkom"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Peran *</span>
            <input
              v-model="org.role"
              placeholder="Ketua Divisi"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1 sm:col-span-2">
            <span class="text-xs font-medium text-slate-700">Periode *</span>
            <input
              v-model="org.period"
              placeholder="2022 - 2024"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            />
          </label>
        </div>
        <label class="space-y-1 block">
          <span class="text-xs font-medium text-slate-700"
            >Deskripsi (1 baris = 1 bullet)</span
          >
          <textarea
            v-model="org.description"
            maxlength="800"
            rows="2"
            placeholder="Koordinasi 20 anggota, selenggarakan 5 workshop&#10;Kelola anggaran Rp 15jt"
            class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs placeholder:text-slate-400 focus:border-slate-900 focus:outline-none"
            @input="autoResize($event)"
          ></textarea>
        </label>
        <p class="text-right text-xs text-slate-400">
          {{ (org.description ?? "").split("\n").filter(Boolean).length }}
          bullet · {{ (org.description ?? "").length }}/800
        </p>
      </div>
      <p v-if="!local.organizations?.length" class="text-xs text-slate-400">
        Belum ada organisasi. Klik Tambah (max 5).
      </p>
    </section>

    <!-- Skills -->
    <section v-show="activeStep === 6" class="space-y-2.5">
      <h2
        class="text-sm font-semibold uppercase tracking-widest text-slate-500"
      >
        Keahlian
      </h2>
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label class="space-y-1">
          <span class="text-xs font-medium text-slate-700"
            >Hard skills (pisah koma)</span
          >
          <input
            v-model="local.skills!.hard"
            maxlength="500"
            placeholder="Go, Laravel, PostgreSQL"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
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
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
          />
        </label>
      </div>
    </section>

    <!-- Lainnya -->
    <section v-show="activeStep === 8" class="space-y-2.5">
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
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
        />
      </label>
      <label class="space-y-1 block">
        <span class="text-xs font-medium text-slate-700">Sertifikat</span>
        <textarea
          v-model="local.certificates"
          maxlength="1000"
          rows="2"
          class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
          @input="autoResize($event)"
        ></textarea>
      </label>
    </section>

    <!-- Proyek -->
    <section v-show="activeStep === 7" class="space-y-2.5">
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
          class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-ink/20"
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
        class="rounded-xl border border-slate-200 p-3 space-y-2.5 dark:border-border"
      >
        <div class="flex justify-between">
          <span
            class="text-xs font-semibold text-slate-500 dark:text-foreground/50"
            >#{{ i + 1 }}</span
          >
          <button
            type="button"
            @click="removeProject(i)"
            class="text-xs text-red-600 hover:underline dark:text-red-400"
          >
            Hapus
          </button>
        </div>
        <div class="grid gap-2.5 sm:grid-cols-2">
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700"
              >Nama proyek *</span
            >
            <input
              v-model="proj.title"
              required
              maxlength="100"
              placeholder="ResumeKan"
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
            />
          </label>
          <label class="space-y-1">
            <span class="text-xs font-medium text-slate-700">Peran *</span>
            <input
              v-model="proj.role"
              required
              maxlength="100"
              placeholder="Fullstack"
              class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
            />
          </label>
        </div>
        <label class="space-y-1 block">
          <span class="text-xs font-medium text-slate-700">Tujuan</span>
          <textarea
            v-model="proj.objective"
            maxlength="500"
            rows="2"
            placeholder="ATS-friendly CV builder, isi form lalu preview dan PDF"
            class="auto-expand w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
            @input="autoResize($event)"
          ></textarea>
        </label>
        <label class="space-y-1 block">
          <span class="text-xs font-medium text-slate-700">Tech stack</span>
          <input
            v-model="proj.techStack"
            maxlength="200"
            placeholder="Vue, Laravel, PostgreSQL"
            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:border-slate-900 focus:outline-none"
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

    <!-- Step navigation -->
    <div class="flex items-center justify-between gap-3 pt-2">
      <button
        v-if="activeStep > 0"
        type="button"
        @click="activeStep--"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-ink/20"
      >
        ← Sebelumnya
      </button>
      <div v-else></div>
      <span class="text-xs text-slate-400 dark:text-foreground/50">
        Langkah {{ activeStep + 1 }} / {{ steps.length }}
      </span>
      <button
        v-if="activeStep < steps.length - 1"
        type="button"
        @click="activeStep++"
        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-main dark:hover:bg-blue-500"
      >
        Selanjutnya →
      </button>
      <button
        v-else
        type="submit"
        :disabled="aiLoading"
        class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-40 dark:bg-main dark:hover:bg-blue-500"
      >
        Simpan CV
      </button>
    </div>
  </form>
</template>

<style scoped>
.auto-expand {
  field-sizing: content;
  min-block-size: 3lh;
  max-block-size: 12lh;
  overflow-y: auto;
  resize: vertical;
}
@supports not (field-sizing: content) {
  .auto-expand {
    min-height: 72px;
  }
}

/* Dark: field & teks form ikut tema (dokumen CvPreview tetap putih) */
.dark input,
.dark select,
.dark textarea {
  border-color: var(--border);
  background-color: color-mix(in srgb, var(--foreground) 12%, transparent);
  color: var(--foreground);
}
.dark input::placeholder,
.dark textarea::placeholder {
  color: color-mix(in srgb, var(--foreground) 40%, transparent);
}
.dark input:focus,
.dark select:focus,
.dark textarea:focus {
  border-color: var(--ring);
}
.dark label > span {
  color: color-mix(in srgb, var(--foreground) 75%, transparent);
}
.dark h2 {
  color: color-mix(in srgb, var(--foreground) 70%, transparent);
}
.dark p {
  color: color-mix(in srgb, var(--foreground) 55%, transparent);
}
.dark nav {
  border-color: var(--border);
}
</style>
