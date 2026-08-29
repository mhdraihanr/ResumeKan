<script setup lang="ts">
import { computed } from "vue";
import type { CvData } from "@/types/cv";

const props = defineProps<{ data: CvData; template: string }>();

const bullets = (s?: string) =>
  (s ?? "")
    .split("\n")
    .map((x) => x.trim())
    .filter(Boolean);

function displayUrl(u?: string) {
  if (!u) return "";
  return u.replace(/^https?:\/\//i, "").replace(/\/$/, "");
}
function hrefUrl(u?: string) {
  if (!u) return "";
  const t = u.trim();
  if (/^https?:\/\//i.test(t)) return t;
  return "https://" + t.replace(/^\/+/, "");
}

const contactLine = computed(() => {
  const p = props.data.personal;
  return [p.email, p.phone, p.address, p.linkedin, p.website]
    .filter(Boolean)
    .join(" · ");
});

const hardList = computed(() =>
  (props.data.skills?.hard ?? "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean),
);
const softList = computed(() =>
  (props.data.skills?.soft ?? "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean),
);

function parseDate(s?: string): number {
  if (!s) return 0;
  const t = s.trim().toLowerCase();
  if (t === "sekarang" || t === "present" || t === "current") return 99999999;
  // coba parse YYYY-MM, YYYY/MM, MM-YYYY, YYYY saja
  const m = t.match(/(\d{4})[^\d]?(\d{1,2})?/);
  if (m) return parseInt(m[1]!) * 100 + parseInt(m[2] || "0");
  return 0;
}
const sortedExperiences = computed(() => {
  const arr = [...(props.data.experiences ?? [])];
  // terbaru di atas: endDate desc, lalu startDate desc, lalu index desc (input terakhir = terbaru)
  return arr
    .map((e, idx) => ({ e, idx }))
    .sort((a, b) => {
      const ea = parseDate(a.e.endDate) - parseDate(b.e.endDate);
      if (ea !== 0) return -ea;
      const sa = parseDate(a.e.startDate) - parseDate(b.e.startDate);
      if (sa !== 0) return -sa;
      return b.idx - a.idx;
    })
    .map((x) => x.e);
});
</script>

<template>
  <!-- Wrapper: A4 print-safe, sama untuk preview & PDF (Browsershot) -->
  <div
    :id="`cv-preview-${template}`"
    :class="[
      'cv-paper mx-auto w-full max-w-[800px] bg-white text-slate-900 antialiased',
      template === 'modern' ? 'font-sans' : 'font-serif',
    ]"
    style="font-size: 11pt; line-height: 1.5"
  >
    <!-- MODERN: VitaeKit — sans, header kiri, navy underline — PDF-like A4 -->
    <template v-if="template === 'modern'">
      <div class="cv-page px-8 py-8 sm:px-10">
        <!-- Header -->
        <header class="mb-6">
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            {{ data.personal.name || "Nama Anda" }}
          </h1>
          <p
            class="mt-1 flex flex-wrap gap-x-2 gap-y-1 text-[10pt] text-slate-600"
          >
            <span v-if="data.personal.email">{{ data.personal.email }}</span>
            <span
              v-if="data.personal.email && data.personal.phone"
              class="text-slate-300"
              >·</span
            >
            <span v-if="data.personal.phone">{{ data.personal.phone }}</span>
            <span
              v-if="
                (data.personal.email || data.personal.phone) &&
                data.personal.address
              "
              class="text-slate-300"
              >·</span
            >
            <span v-if="data.personal.address">{{
              data.personal.address
            }}</span>
            <template v-if="data.personal.linkedin">
              <span class="text-slate-300">·</span>
              <a
                :href="hrefUrl(data.personal.linkedin)"
                target="_blank"
                rel="noopener"
                class="text-[#1e40af] underline decoration-slate-300 underline-offset-2 hover:decoration-[#1e40af]"
                >{{ displayUrl(data.personal.linkedin) }}</a
              >
            </template>
            <template v-if="data.personal.website">
              <span class="text-slate-300">·</span>
              <a
                :href="hrefUrl(data.personal.website)"
                target="_blank"
                rel="noopener"
                class="text-[#1e40af] underline decoration-slate-300 underline-offset-2 hover:decoration-[#1e40af]"
                >{{ displayUrl(data.personal.website) }}</a
              >
            </template>
            <template v-if="data.personal.github">
              <span class="text-slate-300">·</span>
              <a
                :href="hrefUrl(data.personal.github)"
                target="_blank"
                rel="noopener"
                class="text-[#1e40af] underline decoration-slate-300 underline-offset-2 hover:decoration-[#1e40af]"
                >{{ displayUrl(data.personal.github) }}</a
              >
            </template>
            <span
              v-if="
                !data.personal.email &&
                !data.personal.phone &&
                !data.personal.address &&
                !data.personal.linkedin &&
                !data.personal.website &&
                !data.personal.github
              "
              >email · phone · alamat</span
            >
          </p>
        </header>

        <!-- Summary — tanpa title, langsung teks -->
        <section v-if="data.summary" class="mb-5">
          <p class="text-[10pt] leading-relaxed text-slate-700">
            {{ data.summary }}
          </p>
        </section>

        <!-- Experience — terbaru di atas -->
        <section v-if="sortedExperiences.length" class="mb-5">
          <h2
            class="border-b-2 border-[#1e40af] pb-1 text-[10pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Pengalaman Kerja
          </h2>
          <div v-for="(e, i) in sortedExperiences" :key="i" class="mt-3">
            <div class="flex items-baseline justify-between gap-4">
              <p class="text-[10pt] font-semibold text-slate-900">
                {{ e.position || "Posisi" }} · {{ e.company || "Perusahaan" }}
                <span
                  v-if="e.employmentType"
                  class="font-normal text-slate-500"
                >
                  · {{ e.employmentType }}
                </span>
              </p>
              <p class="shrink-0 text-[9pt] text-slate-500">
                {{ e.startDate }} - {{ e.endDate }}
              </p>
            </div>
            <p v-if="e.location" class="text-[9pt] text-slate-500">
              {{ e.location }}
            </p>
            <ul
              v-if="bullets(e.description).length"
              class="mt-1 list-disc pl-5 text-[10pt] text-slate-700"
            >
              <li
                v-for="(b, j) in bullets(e.description)"
                :key="j"
                class="leading-relaxed"
              >
                {{ b }}
              </li>
            </ul>
          </div>
        </section>

        <!-- Education — struktur sama Experience: flex row gelar+tahun, konten full-width -->
        <section v-if="data.education?.length" class="mb-5">
          <h2
            class="border-b-2 border-[#1e40af] pb-1 text-[10pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Pendidikan
          </h2>
          <div v-for="(ed, i) in data.education" :key="i" class="mt-3">
            <div class="flex items-baseline justify-between gap-4">
              <p class="text-[10pt] font-semibold text-slate-900">
                {{ ed.degree }}
              </p>
              <p class="shrink-0 text-[9pt] text-slate-500">{{ ed.year }}</p>
            </div>
            <p class="text-[10pt] text-slate-700">
              {{ ed.institution }}
              <span v-if="ed.location" class="text-slate-700">
                · {{ ed.location }}</span
              >
            </p>
            <p v-if="ed.gpa" class="text-[9pt] text-slate-700">
              IPK: {{ ed.gpa }}
            </p>
            <ul
              v-if="bullets(ed.achievements).length"
              class="mt-1 list-disc pl-5 text-[10pt] text-slate-700"
            >
              <li
                v-for="(b, j) in bullets(ed.achievements)"
                :key="j"
                class="leading-relaxed"
              >
                {{ b }}
              </li>
            </ul>
          </div>
        </section>

        <!-- Organisasi — section terpisah (heading ATS) -->
        <section v-if="data.organizations?.length" class="mb-5">
          <h2
            class="border-b-2 border-[#1e40af] pb-1 text-[10pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Organisasi
          </h2>
          <div v-for="(o, i) in data.organizations" :key="i" class="mt-3">
            <div class="flex items-baseline justify-between gap-4">
              <p class="text-[10pt] font-semibold text-slate-900">
                {{ o.role || "Peran" }} · {{ o.organization || "Organisasi" }}
              </p>
              <p class="shrink-0 text-[9pt] text-slate-500">{{ o.period }}</p>
            </div>
            <ul
              v-if="bullets(o.description).length"
              class="mt-1 list-disc pl-5 text-[10pt] text-slate-700"
            >
              <li
                v-for="(b, j) in bullets(o.description)"
                :key="j"
                class="leading-relaxed"
              >
                {{ b }}
              </li>
            </ul>
          </div>
        </section>

        <!-- Skills -->
        <section v-if="hardList.length || softList.length" class="mb-5">
          <h2
            class="border-b-2 border-[#1e40af] pb-1 text-[10pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Keahlian
          </h2>
          <p v-if="hardList.length" class="mt-2 text-[10pt] text-slate-700">
            <span class="font-semibold">Hard skills:</span>
            {{ hardList.join(" · ") }}
          </p>
          <p v-if="softList.length" class="mt-1 text-[10pt] text-slate-700">
            <span class="font-semibold">Soft skills:</span>
            {{ softList.join(" · ") }}
          </p>
        </section>

        <!-- Projects -->
        <section v-if="data.projects?.length" class="mb-5">
          <h2
            class="border-b-2 border-[#1e40af] pb-1 text-[10pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Proyek
          </h2>
          <div v-for="(p, i) in data.projects" :key="i" class="mt-3">
            <p class="text-[10pt] font-semibold text-slate-900">
              {{ p.title
              }}<span v-if="p.role" class="font-normal text-slate-600">
                · {{ p.role }}</span
              >
            </p>
            <p v-if="p.objective" class="text-[10pt] text-slate-700">
              {{ p.objective }}
            </p>
            <p v-if="p.techStack" class="text-[9pt] text-slate-500">
              <span class="font-semibold">Tech Stack:</span> {{ p.techStack }}
            </p>
          </div>
        </section>

        <!-- Languages / Certificates -->
        <section v-if="data.languages || data.certificates" class="mb-2">
          <h2
            class="border-b-2 border-[#1e40af] pb-1 text-[10pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Lainnya
          </h2>
          <p v-if="data.languages" class="mt-2 text-[10pt] text-slate-700">
            <span class="font-semibold">Bahasa:</span> {{ data.languages }}
          </p>
          <p v-if="data.certificates" class="mt-1 text-[10pt] text-slate-700">
            <span class="font-semibold">Sertifikat:</span>
            {{ data.certificates }}
          </p>
        </section>
      </div>
    </template>

    <!-- CLASSIC: image.png — uppercase nama, garis section, monochrome -->
    <template v-else>
      <div class="cv-page px-8 py-8 sm:px-10 font-serif">
        <!-- Header centered — tanpa garis, spacing rapat ke summary -->
        <header class="mb-3 text-center">
          <h1
            class="text-2xl font-bold uppercase tracking-wide text-slate-900 sm:text-[26px]"
          >
            {{ (data.personal.name || "Nama Anda").toUpperCase() }}
          </h1>
          <p
            class="mt-1 flex flex-wrap justify-center gap-x-2 gap-y-1 text-[10pt] text-slate-600"
          >
            <span v-if="data.personal.email">{{ data.personal.email }}</span>
            <span
              v-if="data.personal.email && data.personal.phone"
              class="text-slate-300"
              >·</span
            >
            <span v-if="data.personal.phone">{{ data.personal.phone }}</span>
            <span
              v-if="
                (data.personal.email || data.personal.phone) &&
                data.personal.address
              "
              class="text-slate-300"
              >·</span
            >
            <span v-if="data.personal.address">{{
              data.personal.address
            }}</span>
            <template v-if="data.personal.linkedin">
              <span class="text-slate-300">·</span>
              <a
                :href="hrefUrl(data.personal.linkedin)"
                target="_blank"
                rel="noopener"
                class="underline decoration-slate-300 underline-offset-2 hover:decoration-slate-900"
                >{{ displayUrl(data.personal.linkedin) }}</a
              >
            </template>
            <template v-if="data.personal.website">
              <span class="text-slate-300">·</span>
              <a
                :href="hrefUrl(data.personal.website)"
                target="_blank"
                rel="noopener"
                class="underline decoration-slate-300 underline-offset-2 hover:decoration-slate-900"
                >{{ displayUrl(data.personal.website) }}</a
              >
            </template>
            <template v-if="data.personal.github">
              <span class="text-slate-300">·</span>
              <a
                :href="hrefUrl(data.personal.github)"
                target="_blank"
                rel="noopener"
                class="underline decoration-slate-300 underline-offset-2 hover:decoration-slate-900"
                >{{ displayUrl(data.personal.github) }}</a
              >
            </template>
            <span
              v-if="
                !data.personal.email &&
                !data.personal.phone &&
                !data.personal.address &&
                !data.personal.linkedin &&
                !data.personal.website &&
                !data.personal.github
              "
              >email · phone · alamat</span
            >
          </p>
        </header>

        <!-- Summary — tanpa title, langsung teks -->
        <section v-if="data.summary" class="mb-5">
          <p class="text-[10pt] leading-relaxed text-slate-700">
            {{ data.summary }}
          </p>
        </section>

        <!-- Experience — terbaru di atas -->
        <section v-if="sortedExperiences.length" class="mb-5">
          <h2
            class="border-b-[1.5px] border-slate-900 pb-1 text-[11pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Pengalaman Kerja
          </h2>
          <div v-for="(e, i) in sortedExperiences" :key="i" class="mt-3">
            <div class="flex items-baseline justify-between gap-4">
              <p class="text-[10pt] font-semibold text-slate-900">
                {{ e.position || "Posisi" }} · {{ e.company || "Perusahaan" }}
                <span
                  v-if="e.employmentType"
                  class="font-normal text-slate-500"
                >
                  · {{ e.employmentType }}
                </span>
              </p>
              <p class="shrink-0 text-[9pt] tabular-nums text-slate-500">
                {{ e.startDate }} - {{ e.endDate }}
              </p>
            </div>
            <p v-if="e.location" class="text-[9pt] text-slate-500">
              {{ e.location }}
            </p>
            <ul
              v-if="bullets(e.description).length"
              class="mt-1 list-disc pl-5 text-[10pt] text-slate-700"
            >
              <li
                v-for="(b, j) in bullets(e.description)"
                :key="j"
                class="leading-relaxed"
              >
                {{ b }}
              </li>
            </ul>
          </div>
        </section>

        <!-- Education — struktur sama Experience: flex row gelar+tahun, konten full-width -->
        <section v-if="data.education?.length" class="mb-5">
          <h2
            class="border-b-[1.5px] border-slate-900 pb-1 text-[11pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Pendidikan
          </h2>
          <div v-for="(ed, i) in data.education" :key="i" class="mt-3">
            <div class="flex items-baseline justify-between gap-4">
              <p class="text-[10pt] font-semibold text-slate-900">
                {{ ed.degree }}
              </p>
              <p class="shrink-0 text-[9pt] tabular-nums text-slate-500">
                {{ ed.year }}
              </p>
            </div>
            <p class="text-[10pt] text-slate-700">
              {{ ed.institution }}
              <span v-if="ed.location" class="text-slate-700">
                · {{ ed.location }}</span
              >
            </p>
            <p v-if="ed.gpa" class="text-[9pt] text-slate-700">
              IPK: {{ ed.gpa }}
            </p>
            <ul
              v-if="bullets(ed.achievements).length"
              class="mt-1 list-disc pl-5 text-[10pt] text-slate-700"
            >
              <li
                v-for="(b, j) in bullets(ed.achievements)"
                :key="j"
                class="leading-relaxed"
              >
                {{ b }}
              </li>
            </ul>
          </div>
        </section>

        <!-- Organisasi — section terpisah (heading ATS) -->
        <section v-if="data.organizations?.length" class="mb-5">
          <h2
            class="border-b-[1.5px] border-slate-900 pb-1 text-[11pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Organisasi
          </h2>
          <div v-for="(o, i) in data.organizations" :key="i" class="mt-3">
            <div class="flex items-baseline justify-between gap-4">
              <p class="text-[10pt] font-semibold text-slate-900">
                {{ o.role || "Peran" }} · {{ o.organization || "Organisasi" }}
              </p>
              <p class="shrink-0 text-[9pt] tabular-nums text-slate-500">
                {{ o.period }}
              </p>
            </div>
            <ul
              v-if="bullets(o.description).length"
              class="mt-1 list-disc pl-5 text-[10pt] text-slate-700"
            >
              <li
                v-for="(b, j) in bullets(o.description)"
                :key="j"
                class="leading-relaxed"
              >
                {{ b }}
              </li>
            </ul>
          </div>
        </section>

        <!-- Skills — pisah Hard/Soft (ATS: hard dominan, soft dibuktikan di bullets) -->
        <section v-if="hardList.length || softList.length" class="mb-5">
          <h2
            class="border-b-[1.5px] border-slate-900 pb-1 text-[11pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Keahlian
          </h2>
          <p
            v-if="hardList.length"
            class="mt-2 text-[10pt] leading-relaxed text-slate-700"
          >
            <span class="font-semibold">Hard skills:</span>
            {{ hardList.join(" · ") }}
          </p>
          <p
            v-if="softList.length"
            class="mt-1 text-[10pt] leading-relaxed text-slate-700"
          >
            <span class="font-semibold">Soft skills:</span>
            {{ softList.join(" · ") }}
          </p>
        </section>

        <!-- Projects -->
        <section v-if="data.projects?.length" class="mb-5">
          <h2
            class="border-b-[1.5px] border-slate-900 pb-1 text-[11pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Proyek
          </h2>
          <div v-for="(p, i) in data.projects" :key="i" class="mt-3">
            <p class="text-[10pt] font-semibold text-slate-900">
              {{ p.title
              }}<span v-if="p.role" class="font-normal text-slate-600">
                · {{ p.role }}</span
              >
            </p>
            <p v-if="p.objective" class="text-[10pt] text-slate-700">
              {{ p.objective }}
            </p>
            <p v-if="p.techStack" class="text-[9pt] text-slate-500">
              <span class="font-semibold">Tech Stack:</span> {{ p.techStack }}
            </p>
          </div>
        </section>

        <!-- Certificates — pisah seperti image.png -->
        <section v-if="data.certificates" class="mb-5">
          <h2
            class="border-b-[1.5px] border-slate-900 pb-1 text-[11pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Sertifikasi
          </h2>
          <p
            class="mt-2 whitespace-pre-line text-[10pt] leading-relaxed text-slate-700"
          >
            {{ data.certificates }}
          </p>
        </section>

        <!-- Languages -->
        <section v-if="data.languages" class="mb-5">
          <h2
            class="border-b-[1.5px] border-slate-900 pb-1 text-[11pt] font-bold uppercase tracking-widest text-slate-900"
          >
            Bahasa
          </h2>
          <p class="mt-2 text-[10pt] text-slate-700">{{ data.languages }}</p>
        </section>
      </div>
    </template>
  </div>
</template>

<style>
@media print {
  .cv-paper {
    max-width: none !important;
  }
  .cv-page {
    padding: 0 !important;
  }
  @page {
    size: A4;
    margin: 14mm 16mm;
  }
  a {
    color: inherit !important;
    text-decoration: none !important;
  }
}
</style>
