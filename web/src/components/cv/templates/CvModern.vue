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
} = useCvData(
  () => props.data,
  () => "modern",
);
</script>

<template>
  <header class="mb-6">
    <h1
      class="text-[36px] font-bold leading-none tracking-tight text-slate-900"
    >
      {{ displayName }}
    </h1>
    <div v-if="hasAnyContact" class="mt-1 space-y-0.5">
      <p
        v-if="contactDirect.length"
        class="flex flex-wrap gap-x-2 text-[10pt] text-slate-600"
      >
        <template v-for="(item, i) in contactDirect" :key="item">
          <span v-if="i > 0" class="text-slate-300">·</span
          ><span>{{ item }}</span>
        </template>
      </p>
      <p
        v-if="contactLinks.length"
        class="flex flex-wrap gap-x-2 text-[10pt] text-slate-600"
      >
        <template v-for="(item, i) in contactLinks" :key="item.href">
          <span v-if="i > 0" class="text-slate-300">·</span>
          <a
            :href="item.href"
            target="_blank"
            rel="noopener"
            class="text-[#1e40af] underline decoration-slate-300 underline-offset-2 hover:decoration-[#1e40af]"
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
    <PreviewSection :title="t.experience" :modern="true" />
    <div v-for="(e, i) in sortedExperiences" :key="i" class="mt-2">
      <EntryRow
        :title="`${e.position || t.position} · ${e.company || t.company}`"
        :period="`${e.startDate} - ${e.endDate}`"
        :modern="true"
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
    <PreviewSection :title="t.education" :modern="true" />
    <div v-for="(ed, i) in data.education" :key="i" class="mt-2">
      <EntryRow :title="ed.degree" :period="ed.year" :modern="true" />
      <p class="text-[10pt] text-slate-700">
        {{ ed.institution }}<span v-if="ed.location"> · {{ ed.location }}</span>
      </p>
      <p v-if="ed.gpa" class="text-[9pt] text-slate-700">
        {{ t.gpa }} {{ ed.gpa }}
      </p>
      <BulletList :items="bullets(ed.achievements)" />
    </div>
  </section>

  <section v-if="data.organizations?.length" class="mb-5">
    <PreviewSection :title="t.organizations" :modern="true" />
    <div v-for="(o, i) in data.organizations" :key="i" class="mt-2">
      <EntryRow
        :title="o.organization || t.organization"
        :period="o.period"
        :modern="true"
      />
      <p v-if="o.role" class="text-[9pt] text-slate-500">{{ o.role }}</p>
      <BulletList :items="bullets(o.description)" />
    </div>
  </section>

  <section v-if="hardList.length || softList.length" class="mb-5">
    <PreviewSection :title="t.skills" :modern="true" />
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
    <PreviewSection :title="t.projects" :modern="true" />
    <div v-for="(p, i) in data.projects" :key="i" class="mt-2">
      <p class="text-[10pt] font-semibold text-slate-900">{{ p.title }}</p>
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

  <section v-if="data.languages || data.certificates" class="mb-2">
    <PreviewSection :title="t.other" :modern="true" />
    <p v-if="data.languages" class="mt-2 text-[10pt] text-slate-700">
      <span class="font-semibold">{{ t.languages }}</span> {{ data.languages }}
    </p>
    <p v-if="data.certificates" class="mt-1 text-[10pt] text-slate-700">
      <span class="font-semibold">{{ t.certificates }}</span>
      {{ data.certificates }}
    </p>
  </section>
</template>
