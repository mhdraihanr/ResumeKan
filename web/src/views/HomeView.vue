<script setup lang="ts">
import { ref } from "vue";
import CvPreview from "@/components/cv/CvPreview.vue";
import { Button } from "@/components/button";
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
} from "@/components/card";
import { Badge } from "@/components/badge";
import {
  FileText,
  LayoutTemplate,
  PenLine,
  Sparkles,
  Download,
} from "lucide-vue-next";
import type { CvData } from "@/types/cv";

const template = ref<"modern" | "classic">("modern");

// Data contoh (bukan fake stats/testimonial, hanya isi CV demo untuk preview asli)
const sample: CvData = {
  personal: {
    name: "Ayu Lestari",
    email: "ayu.lestari@email.com",
    phone: "+62 812-3456-7890",
    address: "Jakarta, Indonesia",
    linkedin: "linkedin.com/in/ayulestari",
    website: "",
    github: "",
  },
  summary:
    "Frontend Developer dengan pengalaman membangun antarmuka web yang cepat dan mudah digunakan. Terbiasa dengan Vue, TypeScript, dan Tailwind CSS.",
  experiences: [
    {
      company: "PT Digital Nusantara",
      position: "Frontend Developer",
      location: "Jakarta, Indonesia",
      employmentType: "Full-time",
      startDate: "Mar 2024",
      endDate: "Sekarang",
      description:
        "Membangun aplikasi dashboard internal dengan Vue 3 dan Pinia.\nBerkolaborasi dengan tim desain untuk menerapkan design system.",
    },
  ],
  education: [
    {
      institution: "Universitas Indonesia",
      degree: "S1 Ilmu Komputer",
      location: "Depok, Indonesia",
      year: "2019 - 2023",
      gpa: "3.80/4.00",
    },
  ],
  organizations: [],
  skills: {
    hard: "Vue.js, TypeScript, Tailwind CSS, Git",
    soft: "Komunikasi, Kerja tim",
  },
  languages: "Indonesia (native), Inggris (menengah)",
  certificates: "",
  projects: [],
};

const features = [
  {
    icon: LayoutTemplate,
    title: "2 template ATS",
    desc: "Modern dan classic, single-column, struktur paragraf yang dibaca mesin.",
  },
  {
    icon: PenLine,
    title: "Simpan draft",
    desc: "Simpan kapan saja tanpa keluar form. Lanjut nanti, tidak hilang.",
  },
  {
    icon: Sparkles,
    title: "Ringkasan AI",
    desc: "Ubah pengalaman kerja jadi ringkasan ringkas berbantuan AI.",
  },
  {
    icon: Download,
    title: "Download PDF A4",
    desc: "PDF siap lamar, rapi di cetak dan dibaca ATS.",
  },
];
</script>

<template>
  <main class="bg-paper dark:bg-background">
    <!-- HERO -->
    <section class="border-b-2 border-ink dark:border-border">
      <div
        class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-24"
      >
        <div>
          <Badge class="bg-powder text-ink" variant="neutral">Generator CV ATS</Badge>
          <h1
            class="mt-4 text-4xl font-black leading-tight tracking-tight text-ink dark:text-foreground sm:text-5xl"
            v-motion
            :initial="{ opacity: 0, y: 12 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 300 } }"
          >
            Buat CV yang dibaca ATS dan manusia.
          </h1>
          <p class="mt-4 max-w-md text-base leading-relaxed text-ink/70 dark:text-foreground/70">
            Isi form terstruktur, pilih template, dan unduh PDF siap lamar.
            Tanpa ribet, tanpa template aneh.
          </p>
          <div class="mt-6 flex flex-wrap gap-3">
            <RouterLink to="/register">
              <Button size="lg">Buat CV pertama</Button>
            </RouterLink>
            <RouterLink to="/login">
              <Button variant="neutral" size="lg">Masuk</Button>
            </RouterLink>
          </div>
        </div>

        <!-- Mock browser window dengan CvPreview asli -->
        <div
          v-motion
          :initial="{ opacity: 0, y: 12 }"
          :enter="{
            opacity: 1,
            y: 0,
            transition: { delay: 150, duration: 300 },
          }"
        >
          <div
            class="overflow-hidden rounded-base border-3 border-ink bg-white shadow-[8px_8px_0_0_#0f172a] dark:border-border dark:bg-secondary-background dark:shadow-[8px_8px_0_0_#09090b]"
          >
            <!-- Title bar -->
            <div
              class="flex items-center gap-2 border-b-2 border-ink bg-ink px-4 py-2.5"
            >
              <span class="size-2.5 rounded-full bg-red-500"></span>
              <span class="size-2.5 rounded-full bg-yellow-400"></span>
              <span class="size-2.5 rounded-full bg-emerald-400"></span>
              <span class="ml-3 text-xs font-medium text-paper/80"
                >resumekan.app/preview</span
              >
            </div>
            <!-- Toggle template -->
            <div
              class="flex items-center justify-center gap-2 border-b-2 border-ink bg-paper px-4 py-2.5 dark:bg-secondary-background"
            >
              <button
                v-for="t in ['modern', 'classic'] as const"
                :key="t"
                @click="template = t"
                class="rounded-base border-2 border-ink px-3 py-1 text-xs font-medium transition"
                :class="
                  template === t
                    ? 'bg-navy text-white shadow-[2px_2px_0_0_#0f172a]'
                    : 'bg-white text-ink hover:bg-paper dark:bg-secondary-background dark:text-foreground dark:hover:bg-main/20'
                "
              >
                {{ t === "modern" ? "Modern" : "Classic" }}
              </button>
            </div>
            <!-- Live preview -->
            <div class="max-h-[480px] overflow-auto p-4">
              <CvPreview :data="sample" :template="template" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FITUR: bg ink tetap gelap di kedua mode (identitas section) -->
    <section class="border-b-2 border-ink dark:border-border bg-ink">
      <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h2 class="text-3xl font-black tracking-tight text-paper">
          Semua yang kamu butuh, tanpa yang tidak perlu.
        </h2>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <Card
            v-for="f in features"
            :key="f.title"
            v-motion
            :initial="{ opacity: 0, y: 12 }"
            :enter="{
              opacity: 1,
              y: 0,
              transition: { delay: 100, duration: 300 },
            }"
            class="border-2 border-ink bg-secondary-background dark:border-border"
          >
            <CardHeader>
              <div
                class="flex size-11 items-center justify-center rounded-base border-2 border-ink bg-powder dark:border-border"
              >
                <component :is="f.icon" class="size-5 text-ink" />
              </div>
              <CardTitle class="text-lg font-black text-foreground">{{
                f.title
              }}</CardTitle>
              <CardDescription class="text-sm text-foreground/70">{{
                f.desc
              }}</CardDescription>
            </CardHeader>
          </Card>
        </div>
      </div>
    </section>

    <!-- TEMPLATE -->
    <section class="border-b-2 border-ink dark:border-border bg-paper dark:bg-background">
      <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h2 class="text-3xl font-black tracking-tight text-ink dark:text-foreground">
          Dua template, satu standar ATS.
        </h2>
        <p class="mt-2 max-w-lg text-base text-ink/70 dark:text-foreground/70">
          Modern untuk kesan segar, classic untuk kesan formal. Keduanya
          single-column dan siap dibaca mesin.
        </p>
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
          <Card
            v-motion
            :initial="{ opacity: 0, y: 12 }"
            :enter="{ opacity: 1, y: 0 }"
          >
            <CardHeader>
              <CardTitle class="text-lg font-black text-foreground"
                >Modern</CardTitle
              >
              <CardDescription class="text-sm text-foreground/70">
                Sans-serif, header nama besar, aksen navy di judul section.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div
                class="max-h-64 overflow-auto rounded-base border-2 border-ink p-3 dark:border-border"
              >
                <CvPreview :data="sample" template="modern" />
              </div>
            </CardContent>
          </Card>
          <Card
            v-motion
            :initial="{ opacity: 0, y: 12 }"
            :enter="{ opacity: 1, y: 0, transition: { delay: 100 } }"
          >
            <CardHeader>
              <CardTitle class="text-lg font-black text-foreground"
                >Classic</CardTitle
              >
              <CardDescription class="text-sm text-foreground/70">
                Serif, nama kapital di tengah, kesan formal dan elegan.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div
                class="max-h-64 overflow-auto rounded-base border-2 border-ink p-3 dark:border-border"
              >
                <CvPreview :data="sample" template="classic" />
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </section>

    <!-- CTA AKHIR: bg navy identitas, teks putih di kedua mode (navy 3b82f6 di dark tetap cukup) -->
    <section class="bg-navy dark:bg-main">
      <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6">
        <h2 class="text-3xl font-black tracking-tight text-white">
          CV kamu butuh start. Mulai di sini.
        </h2>
        <p class="mx-auto mt-3 max-w-md text-base text-white/80">
          Gratis daftar, langsung bisa isi CV pertama kamu.
        </p>
        <div class="mt-6">
          <RouterLink to="/register">
            <Button
              size="lg"
              class="border-ink bg-paper text-ink shadow-[4px_4px_0_0_#0f172a] hover:translate-x-boxShadowX hover:translate-y-boxShadowY hover:shadow-none dark:bg-secondary-background dark:text-foreground dark:shadow-[4px_4px_0_0_#04060a]"
            >
              Coba sekarang, gratis
            </Button>
          </RouterLink>
        </div>
      </div>
    </section>
  </main>
</template>
