<script setup lang="ts">
import { computed } from "vue";
import type { CvData } from "@/types/cv";
import PreviewSection from "./sections/PreviewSection.vue";
import EntryRow from "./sections/EntryRow.vue";
import BulletList from "./sections/BulletList.vue";
import { getTemplateConfig } from "@/lib/cv-templates";

const props = withDefaults(
  defineProps<{ data: CvData; template: string; compact?: boolean }>(),
  {
    compact: false,
  },
);

const tpl = computed(() => getTemplateConfig(props.template));
const isModern = computed(() => tpl.value.id === "modern");

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

const contactDirect = computed(() => {
  const p = props.data.personal;
  return [p.email, p.phone, p.address].filter(Boolean);
});
const contactLinks = computed(() => {
  const p = props.data.personal;
  return [
    { label: displayUrl(p.linkedin), href: hrefUrl(p.linkedin) },
    { label: displayUrl(p.website), href: hrefUrl(p.website) },
    { label: displayUrl(p.github), href: hrefUrl(p.github) },
  ].filter((x) => x.label);
});
const hasAnyContact = computed(
  () => contactDirect.value.length > 0 || contactLinks.value.length > 0,
);

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
  const m = t.match(/(\d{4})[^\d]?(\d{1,2})?/);
  if (m) return parseInt(m[1]!) * 100 + parseInt(m[2] || "0");
  return 0;
}
const sortedExperiences = computed(() => {
  const arr = [...(props.data.experiences ?? [])];
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

const displayName = computed(() =>
  tpl.value.nameUppercase
    ? (props.data.personal.name || "Nama Anda").toUpperCase()
    : props.data.personal.name || "Nama Anda",
);
</script>

<template>
  <div
    :id="`cv-preview-${tpl.id}`"
    :class="[
      'cv-paper mx-auto w-full max-w-[800px] bg-white text-slate-900 antialiased',
      tpl.font,
    ]"
    style="font-size: 11pt; line-height: 1.5"
  >
    <div :class="['cv-page', compact ? 'px-0 py-4' : 'px-8 py-8 sm:px-10']">
      <!-- Header: token-driven (align, uppercase, margin, link color) -->
      <header :class="[tpl.headerMargin, tpl.headerAlign === 'center' ? 'text-center' : '']">
        <h1 :class="tpl.h1Class">{{ displayName }}</h1>
        <div v-if="hasAnyContact" class="mt-1 space-y-0.5">
          <p
            v-if="contactDirect.length"
            :class="[
              'flex flex-wrap gap-x-2 text-[10pt] text-slate-600',
              tpl.headerAlign === 'center' ? 'justify-center' : '',
            ]"
          >
            <template v-for="(item, i) in contactDirect" :key="item">
              <span v-if="i > 0" class="text-slate-300">·</span>
              <span>{{ item }}</span>
            </template>
          </p>
          <p
            v-if="contactLinks.length"
            :class="[
              'flex flex-wrap gap-x-2 text-[10pt] text-slate-600',
              tpl.headerAlign === 'center' ? 'justify-center' : '',
            ]"
          >
            <template v-for="(item, i) in contactLinks" :key="item.href">
              <span v-if="i > 0" class="text-slate-300">·</span>
              <a :href="item.href" target="_blank" rel="noopener" :class="tpl.linkClass">{{ item.label }}</a>
            </template>
          </p>
        </div>
        <p v-else class="mt-1 text-[10pt] text-slate-600">email · phone · alamat</p>
      </header>

      <!-- Summary -->
      <section v-if="data.summary" class="mb-5">
        <p class="text-[10pt] leading-relaxed text-slate-700">{{ data.summary }}</p>
      </section>

      <!-- Experience -->
      <section v-if="sortedExperiences.length" class="mb-5">
        <PreviewSection title="Pengalaman Kerja" :modern="isModern" />
        <div v-for="(e, i) in sortedExperiences" :key="i" class="mt-3">
          <EntryRow :title="`${e.position || 'Posisi'} · ${e.company || 'Perusahaan'}`" :period="`${e.startDate} - ${e.endDate}`" :modern="isModern" />
          <p v-if="e.employmentType || e.location" class="text-[9pt] text-slate-500">
            <span v-if="e.employmentType">{{ e.employmentType }}</span>
            <span v-if="e.employmentType && e.location"> · </span>
            <span v-if="e.location">{{ e.location }}</span>
          </p>
          <BulletList :items="bullets(e.description)" />
        </div>
      </section>

      <!-- Education -->
      <section v-if="data.education?.length" class="mb-5">
        <PreviewSection title="Pendidikan" :modern="isModern" />
        <div v-for="(ed, i) in data.education" :key="i" class="mt-3">
          <EntryRow :title="ed.degree" :period="ed.year" :modern="isModern" />
          <p class="text-[10pt] text-slate-700">
            {{ ed.institution }}<span v-if="ed.location" class="text-slate-700"> · {{ ed.location }}</span>
          </p>
          <p v-if="ed.gpa" class="text-[9pt] text-slate-700">IPK: {{ ed.gpa }}</p>
          <BulletList :items="bullets(ed.achievements)" />
        </div>
      </section>

      <!-- Organization -->
      <section v-if="data.organizations?.length" class="mb-5">
        <PreviewSection title="Organisasi" :modern="isModern" />
        <div v-for="(o, i) in data.organizations" :key="i" class="mt-3">
          <EntryRow :title="o.organization || 'Organisasi'" :period="o.period" :modern="isModern" />
          <p v-if="o.role" class="text-[9pt] text-slate-500">{{ o.role }}</p>
          <BulletList :items="bullets(o.description)" />
        </div>
      </section>

      <!-- Skills -->
      <section v-if="hardList.length || softList.length" class="mb-5">
        <PreviewSection title="Keahlian" :modern="isModern" />
        <p v-if="hardList.length" class="mt-2 text-[10pt] leading-relaxed text-slate-700">
          <span class="font-semibold">Hard skills:</span> {{ hardList.join(" · ") }}
        </p>
        <p v-if="softList.length" class="mt-1 text-[10pt] leading-relaxed text-slate-700">
          <span class="font-semibold">Soft skills:</span> {{ softList.join(" · ") }}
        </p>
      </section>

      <!-- Projects -->
      <section v-if="data.projects?.length" class="mb-5">
        <PreviewSection title="Proyek" :modern="isModern" />
        <div v-for="(p, i) in data.projects" :key="i" class="mt-3">
          <p class="text-[10pt] font-semibold text-slate-900">{{ p.title }}</p>
          <p v-if="p.objective" class="text-[10pt] text-slate-700">{{ p.objective }}</p>
          <p v-if="p.role" class="text-[9pt] text-slate-500"><span class="font-semibold">Peran:</span> {{ p.role }}</p>
          <p v-if="p.techStack" class="text-[9pt] text-slate-500"><span class="font-semibold">Tech Stack:</span> {{ p.techStack }}</p>
        </div>
      </section>

      <!-- Other: combined (modern) vs split (classic) -->
      <template v-if="tpl.otherMode === 'combined'">
        <section v-if="data.languages || data.certificates" class="mb-2">
          <PreviewSection title="Lainnya" :modern="isModern" />
          <p v-if="data.languages" class="mt-2 text-[10pt] text-slate-700"><span class="font-semibold">Bahasa:</span> {{ data.languages }}</p>
          <p v-if="data.certificates" class="mt-1 text-[10pt] text-slate-700"><span class="font-semibold">Sertifikat:</span> {{ data.certificates }}</p>
        </section>
      </template>
      <template v-else>
        <section v-if="data.certificates" class="mb-5">
          <PreviewSection title="Sertifikasi" :modern="isModern" />
          <p class="mt-2 whitespace-pre-line text-[10pt] leading-relaxed text-slate-700">{{ data.certificates }}</p>
        </section>
        <section v-if="data.languages" class="mb-5">
          <PreviewSection title="Bahasa" :modern="isModern" />
          <p class="mt-2 text-[10pt] text-slate-700">{{ data.languages }}</p>
        </section>
      </template>
    </div>
  </div>
</template>

<style>
@media print {
  .cv-paper { max-width: none !important; }
  .cv-page { padding: 0 !important; }
  @page { size: A4; margin: 14mm 16mm; }
  a { color: inherit !important; text-decoration: none !important; }
}
</style>
