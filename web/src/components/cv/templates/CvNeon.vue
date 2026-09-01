<script setup lang="ts">
import { computed } from "vue";
import type { CvData } from "@/types/cv";
import { useCvData } from "@/composables/useCvData";
import BulletList from "../sections/BulletList.vue";

const props = defineProps<{ data: CvData }>();
const {
  displayName,
  displayUrl,
  hrefUrl,
  bullets,
  hardList,
  softList,
  sortedExperiences,
} = useCvData(
  () => props.data,
  () => "neon",
);

// Ikon Material inline (SVG path) untuk tiap tipe kontak, mengikuti referensi.
const icons: Record<string, string> = {
  location:
    "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
  email:
    "M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z",
  phone:
    "M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z",
  linkedin:
    "M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zm-7 9v5h2v-5h2l.5-3H14V8c0-.6.4-1 1-1h1V4h-2c-2 0-3 1-3 3v2H9v3h3z",
  website:
    "M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z",
  github:
    "M12 2C6.48 2 2 6.48 2 12c0 4.42 2.87 8.17 6.84 9.5.5.08.66-.23.66-.5v-1.69c-2.77.6-3.36-1.34-3.36-1.34-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.87 1.52 2.34 1.07 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.92 0-1.11.38-2 1.03-2.71-.1-.25-.45-1.29.1-2.64 0 0 .84-.27 2.75 1.02.79-.22 1.65-.33 2.5-.33.85 0 1.71.11 2.5.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.35.2 2.39.1 2.64.65.71 1.03 1.6 1.03 2.71 0 3.82-2.34 4.66-4.57 4.91.36.31.69.92.69 1.85V21c0 .27.16.59.67.5C19.14 20.16 22 16.42 22 12A10 10 0 0012 2z",
};

const contactItems = computed(() => {
  const personal = props.data.personal;
  return [
    { icon: "location", label: personal.address, href: "" },
    { icon: "email", label: personal.email, href: `mailto:${personal.email}` },
    { icon: "phone", label: personal.phone, href: `tel:${personal.phone}` },
    {
      icon: "linkedin",
      label: displayUrl(personal.linkedin),
      href: hrefUrl(personal.linkedin),
    },
    {
      icon: "website",
      label: displayUrl(personal.website),
      href: hrefUrl(personal.website),
    },
    {
      icon: "github",
      label: displayUrl(personal.github),
      href: hrefUrl(personal.github),
    },
  ].filter((item) => item.label);
});
</script>

<template>
  <header class="mb-5 flex items-center justify-between gap-6">
    <div class="min-w-0 flex-1">
      <h1
        class="text-[40px] font-bold leading-none tracking-[-0.04em] text-[#111]"
      >
        {{ displayName }}
      </h1>
      <div
        v-if="contactItems.length"
        class="mt-3 grid grid-cols-1 gap-x-4 gap-y-1 text-[13px] leading-snug text-[#444] sm:grid-cols-2"
      >
        <template v-for="item in contactItems" :key="item.label">
          <div class="flex items-center gap-2">
            <span
              class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#6ee7b7] text-white"
            >
              <svg
                viewBox="0 0 24 24"
                class="h-3.5 w-3.5"
                aria-hidden="true"
                focusable="false"
              >
                <path :d="icons[item.icon]" fill="currentColor" />
              </svg>
            </span>
            <a
              v-if="item.href"
              :href="item.href"
              target="_blank"
              rel="noopener"
              class="min-w-0 break-words hover:underline"
            >
              {{ item.label }}
            </a>
            <span v-else class="min-w-0 break-words">{{ item.label }}</span>
          </div>
        </template>
      </div>
    </div>
    <img
      v-if="data.personal.photo"
      :src="data.personal.photo"
      :alt="`Foto profil ${displayName}`"
      class="h-[135px] w-[110px] shrink-0 self-center rounded object-cover"
    />
  </header>

  <p v-if="data.summary" class="mb-5 text-[14px] leading-relaxed text-[#444]">
    {{ data.summary }}
  </p>

  <section v-if="sortedExperiences.length" class="mb-5">
    <h2
      class="border-b-2 border-[#6ee7b7] pb-1.5 text-[16px] font-bold uppercase tracking-[0.03em] text-[#111]"
    >
      Pengalaman Kerja
    </h2>
    <div
      v-for="(experience, index) in sortedExperiences"
      :key="index"
      class="mt-3"
    >
      <div
        class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-5"
      >
        <div class="min-w-0 text-[10.5pt] text-[#111]">
          <p>
            <span class="font-bold"
              >{{ experience.position || "Posisi" }},</span
            >
            <em v-if="experience.company" class="ml-1 text-[#444]">{{
              experience.company
            }}</em>
          </p>
          <p
            v-if="experience.employmentType"
            class="text-[9pt] leading-snug text-[#444]"
          >
            {{ experience.employmentType }}
          </p>
        </div>
        <div class="shrink-0 text-[9pt] leading-snug text-[#444] sm:text-right">
          <p class="font-semibold text-[#111]">
            {{ experience.startDate }} - {{ experience.endDate }}
          </p>
          <p v-if="experience.location">{{ experience.location }}</p>
        </div>
      </div>
      <BulletList :items="bullets(experience.description)" />
    </div>
  </section>

  <section v-if="data.education?.length" class="mb-5">
    <h2
      class="border-b-2 border-[#6ee7b7] pb-1.5 text-[16px] font-bold uppercase tracking-[0.03em] text-[#111]"
    >
      Pendidikan
    </h2>
    <div v-for="(education, index) in data.education" :key="index" class="mt-3">
      <div
        class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-5"
      >
        <div class="min-w-0 text-[10.5pt] text-[#111]">
          <p>
            <span class="font-bold">{{ education.degree }},</span>
            <em class="ml-1 text-[#444]">{{ education.institution }}</em>
          </p>
          <p v-if="education.gpa" class="text-[9pt] leading-snug text-[#444]">
            IPK:
            <span class="font-semibold text-[#111]">{{ education.gpa }}</span>
          </p>
        </div>
        <div class="shrink-0 text-[9pt] leading-snug text-[#444] sm:text-right">
          <p class="font-semibold text-[#111]">{{ education.year }}</p>
          <p v-if="education.location">{{ education.location }}</p>
        </div>
      </div>
      <BulletList :items="bullets(education.achievements)" />
    </div>
  </section>

  <section v-if="hardList.length || softList.length" class="mb-5">
    <h2
      class="border-b-2 border-[#6ee7b7] pb-1.5 text-[16px] font-bold uppercase tracking-[0.03em] text-[#111]"
    >
      Keahlian
    </h2>
    <p
      v-if="hardList.length"
      class="mt-2 text-[10pt] leading-relaxed text-[#111]"
    >
      <span class="font-semibold text-[#111]">Hard skills:</span>
      {{ hardList.join(" · ") }}
    </p>
    <p
      v-if="softList.length"
      class="mt-1 text-[10pt] leading-relaxed text-[#444]"
    >
      <span class="font-semibold text-[#111]">Soft skills:</span>
      {{ softList.join(" · ") }}
    </p>
  </section>

  <section v-if="data.organizations?.length" class="mb-5">
    <h2
      class="border-b-2 border-[#6ee7b7] pb-1.5 text-[16px] font-bold uppercase tracking-[0.03em] text-[#111]"
    >
      Organisasi
    </h2>
    <div
      v-for="(organization, index) in data.organizations"
      :key="index"
      class="mt-3"
    >
      <div
        class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-5"
      >
        <p class="text-[10.5pt] text-[#111]">
          <span class="font-bold">{{ organization.organization }}</span>
          <em v-if="organization.role" class="ml-1 text-[#444]">{{
            organization.role
          }}</em>
        </p>
        <p class="shrink-0 text-[9pt] text-[#444] sm:text-right">
          <span class="font-semibold text-[#111]">{{
            organization.period
          }}</span>
        </p>
      </div>
      <BulletList :items="bullets(organization.description)" />
    </div>
  </section>

  <section v-if="data.projects?.length" class="mb-5">
    <h2
      class="border-b-2 border-[#6ee7b7] pb-1.5 text-[16px] font-bold uppercase tracking-[0.03em] text-[#111]"
    >
      Proyek
    </h2>
    <div v-for="(project, index) in data.projects" :key="index" class="mt-3">
      <p class="text-[10.5pt] font-bold text-[#111]">{{ project.title }}</p>
      <p
        v-if="project.objective"
        class="mt-0.5 text-[10pt] leading-relaxed text-[#444]"
      >
        {{ project.objective }}
      </p>
      <p v-if="project.role" class="mt-0.5 text-[9pt] text-[#444]">
        <span class="font-semibold text-[#111]">Peran:</span> {{ project.role }}
      </p>
      <p v-if="project.techStack" class="text-[9pt] text-[#444]">
        <span class="font-semibold text-[#111]">Tech Stack:</span>
        {{ project.techStack }}
      </p>
    </div>
  </section>

  <div
    v-if="data.languages || data.certificates"
    class="grid gap-7 sm:grid-cols-2 sm:gap-10"
  >
    <section v-if="data.languages">
      <h2
        class="border-b-2 border-[#6ee7b7] pb-1.5 text-[16px] font-bold uppercase tracking-[0.03em] text-[#111]"
      >
        Bahasa
      </h2>
      <p class="mt-2 text-[10pt] leading-relaxed text-[#444]">
        {{ data.languages }}
      </p>
    </section>
    <section v-if="data.certificates">
      <h2
        class="border-b-2 border-[#6ee7b7] pb-1.5 text-[16px] font-bold uppercase tracking-[0.03em] text-[#111]"
      >
        Sertifikat
      </h2>
      <BulletList :items="bullets(data.certificates)" />
    </section>
  </div>
</template>
