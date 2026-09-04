<script setup lang="ts">
import { computed } from "vue";
import type { CvData } from "@/types/cv";
import { useCvData } from "@/composables/useCvData";
import { getLabels } from "@/lib/cv-labels";
import PreviewSection from "../sections/PreviewSection.vue";
import EntryRow from "../sections/EntryRow.vue";
import BulletList from "../sections/BulletList.vue";

const props = defineProps<{ data: CvData; language?: string }>();
const t = computed(() => getLabels(props.language));
const {
  displayName,
  contactDirect,
  contactLinks,
  hasAnyContact,
  bullets,
  hardList,
  softList,
  sortedExperiences,
  hrefUrl,
} = useCvData(
  () => props.data,
  () => "classic",
);
</script>

<template>
  <header class="mb-3 text-center">
    <h1
      class="text-[30px] font-bold uppercase tracking-wide text-slate-900 leading-tight sm:text-[32px]"
    >
      {{ displayName }}
    </h1>
    <div v-if="hasAnyContact" class="mt-1 space-y-0.5">
      <p
        v-if="contactDirect.length"
        class="flex flex-wrap justify-center gap-x-2 text-[10pt] text-slate-600"
      >
        <template v-for="(item, i) in contactDirect" :key="item">
          <span v-if="i > 0" class="text-slate-300">·</span
          ><span>{{ item }}</span>
        </template>
      </p>
      <p
        v-if="contactLinks.length"
        class="flex flex-wrap justify-center gap-x-2 text-[10pt] text-slate-600"
      >
        <template v-for="(item, i) in contactLinks" :key="item.href">
          <span v-if="i > 0" class="text-slate-300">·</span>
          <a
            :href="item.href"
            target="_blank"
            rel="noopener"
            class="underline decoration-slate-300 underline-offset-2 hover:decoration-slate-900"
            >{{ item.label }}</a
          >
        </template>
      </p>
    </div>
    <p v-else class="mt-1 text-[10pt] text-slate-600">
      email · phone · address
    </p>
  </header>

  <section v-if="data.summary" class="mb-5">
    <p class="text-[10pt] leading-relaxed text-slate-700">{{ data.summary }}</p>
  </section>

  <section v-if="sortedExperiences.length" class="mb-5">
    <PreviewSection :title="t.experience" />
    <div v-for="(e, i) in sortedExperiences" :key="i" class="mt-2">
      <EntryRow
        :title="`${e.position || t.position} · ${e.company || t.company}`"
        :period="`${e.startDate} - ${e.endDate}`"
      />
      <p
        v-if="e.employmentType || e.location"
        class="text-[9pt] text-slate-500"
      >
        <span v-if="e.employmentType">{{ e.employmentType }}</span>
        <span v-if="e.employmentType && e.location"> · </span>
        <span v-if="e.location">{{ e.location }}</span>
      </p>
      <BulletList :items="bullets(e.description)" />
    </div>
  </section>

  <section v-if="data.education?.length" class="mb-5">
    <PreviewSection :title="t.education" />
    <div v-for="(ed, i) in data.education" :key="i" class="mt-2">
      <EntryRow :title="ed.degree" :period="ed.year" />
      <p class="text-[10pt] text-slate-700">
        {{ ed.institution }}<span v-if="ed.location"> · {{ ed.location }}</span>
      </p>
      <p v-if="ed.gpa" class="text-[9pt] text-slate-700">
        {{ t.gpa }} <span class="font-semibold">{{ ed.gpa }}</span>
      </p>
      <BulletList :items="bullets(ed.achievements)" />
    </div>
  </section>

  <section v-if="data.organizations?.length" class="mb-5">
    <PreviewSection :title="t.organizations" />
    <div v-for="(o, i) in data.organizations" :key="i" class="mt-2">
      <EntryRow :title="o.organization || t.organization" :period="o.period" />
      <p v-if="o.role" class="text-[9pt] text-slate-500">{{ o.role }}</p>
      <BulletList :items="bullets(o.description)" />
    </div>
  </section>

  <section v-if="hardList.length || softList.length" class="mb-5">
    <PreviewSection :title="t.skills" />
    <p
      v-if="hardList.length"
      class="mt-2 text-[10pt] leading-relaxed text-slate-700"
    >
      <span class="font-semibold">{{ t.hardSkills }}</span>
      {{ hardList.join(" · ") }}
    </p>
    <p
      v-if="softList.length"
      class="mt-1 text-[10pt] leading-relaxed text-slate-700"
    >
      <span class="font-semibold">{{ t.softSkills }}</span>
      {{ softList.join(" · ") }}
    </p>
  </section>

  <section v-if="data.projects?.length" class="mb-5">
    <PreviewSection :title="t.projects" />
    <div v-for="(p, i) in data.projects" :key="i" class="mt-2">
      <p class="text-[10pt] font-semibold text-slate-900">
        {{ p.title }}
        <a
          v-if="p.link"
          :href="hrefUrl(p.link)"
          target="_blank"
          rel="noopener"
          :aria-label="`Buka link proyek ${p.title}`"
          class="ml-1 inline-block align-baseline hover:underline"
          ><svg
            viewBox="0 0 24 24"
            class="inline h-3 w-3"
            aria-hidden="true"
            focusable="false"
          >
            <path
              fill="currentColor"
              d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42L17.59 5H14V3zM5 5h6v2H7v10h10v-4h2v6H5V5z"
            /></svg
        ></a>
      </p>
      <p v-if="p.objective" class="text-[10pt] text-slate-700">
        {{ p.objective }}
      </p>
      <p v-if="p.role" class="text-[9pt] text-slate-500">
        <span class="font-semibold">{{ t.role }}</span> {{ p.role }}
      </p>
      <p v-if="p.techStack" class="text-[9pt] text-slate-500">
        <span class="font-semibold">{{ t.techStack }}</span> {{ p.techStack }}
      </p>
    </div>
  </section>

  <section v-if="data.certificates?.length" class="mb-5">
    <PreviewSection :title="t.certificates" />
    <div v-for="(c, i) in data.certificates" :key="i" class="mt-2">
      <div class="flex items-baseline justify-between gap-4">
        <p class="text-[10pt] text-slate-700">
          {{ c.name }} <span class="font-semibold">by {{ c.issuer }}</span>
        </p>
        <p class="shrink-0 text-[9pt] tabular-nums text-slate-500">
          {{ c.year }}
        </p>
      </div>
      <p v-if="c.credentialId" class="text-[9pt] text-slate-500">
        ID: {{ c.credentialId }}
      </p>
    </div>
  </section>
  <section v-if="data.languages" class="mb-5">
    <PreviewSection :title="t.languages" />
    <p class="mt-2 text-[10pt] text-slate-700">{{ data.languages }}</p>
  </section>
</template>
