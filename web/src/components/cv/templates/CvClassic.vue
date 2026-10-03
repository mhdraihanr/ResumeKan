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
  skillGroups,
  sortedExperiences,
  hrefUrl,
} = useCvData(
  () => props.data,
  () => "classic",
  () => props.language ?? "id",
);

// Ink T1/T2 classic = hitam pekat `text-black` (#000), ditulis langsung di class.
// T3 (periode/tahun) tetap `text-neutral-800` dan T4 dekoratif tetap
// `text-neutral-400` supaya hierarki tipografi terjaga (docs/DESIGN.md §4).
const INK = "text-black";
// Garis bawah heading section (T4 struktural) ikut hitam pekat untuk classic.
const RULE = "border-black";
</script>

<template>
  <header class="mb-3 text-center print:break-after-avoid">
    <h1
      class="text-[24pt] font-bold uppercase tracking-wide leading-tight"
      :class="INK"
    >
      {{ displayName }}
    </h1>
    <div v-if="hasAnyContact" class="mt-1 space-y-0.5">
      <p
        v-if="contactDirect.length"
        class="flex flex-wrap justify-center gap-x-2 text-[10pt]"
        :class="INK"
      >
        <template v-for="(item, i) in contactDirect" :key="item">
          <span v-if="i > 0" class="text-neutral-400">·</span
          ><span>{{ item }}</span>
        </template>
      </p>
      <p
        v-if="contactLinks.length"
        class="flex flex-wrap justify-center gap-x-2 text-[10pt]"
        :class="INK"
      >
        <template v-for="(item, i) in contactLinks" :key="item.href">
          <span v-if="i > 0" class="text-neutral-400">·</span>
          <a
            :href="item.href"
            target="_blank"
            rel="noopener"
            class="underline decoration-neutral-400 underline-offset-2 hover:decoration-black"
            :class="INK"
            >{{ item.label }}</a
          >
        </template>
      </p>
    </div>
    <p v-else class="mt-1 text-[10pt] text-neutral-800">
      email · phone · address
    </p>
  </header>

  <section v-if="data.summary" class="mb-5">
    <p class="text-[10pt] leading-relaxed" :class="INK">
      {{ data.summary }}
    </p>
  </section>

  <section v-if="sortedExperiences.length" class="mb-5">
    <PreviewSection :title="t.experience" :ink-class="INK" :rule-class="RULE" />
    <div v-for="(e, i) in sortedExperiences" :key="i" class="mt-2 cv-entry">
      <EntryRow
        :title="`${e.position || t.position} · ${e.company || t.company}`"
        :period="`${e.startDate} - ${e.endDate}`"
        :ink-class="INK"
      />
      <p v-if="e.employmentType || e.location" class="text-[9pt]" :class="INK">
        <span v-if="e.employmentType">{{ e.employmentType }}</span>
        <span v-if="e.employmentType && e.location"> · </span>
        <span v-if="e.location">{{ e.location }}</span>
      </p>
      <BulletList :items="bullets(e.description)" :ink-class="INK" />
    </div>
  </section>

  <section v-if="data.education?.length" class="mb-5">
    <PreviewSection :title="t.education" :ink-class="INK" :rule-class="RULE" />
    <div v-for="(ed, i) in data.education" :key="i" class="mt-2 cv-entry">
      <EntryRow :title="ed.degree" :period="ed.year" :ink-class="INK" />
      <p class="text-[10pt]" :class="INK">
        {{ ed.institution }}<span v-if="ed.location"> · {{ ed.location }}</span>
      </p>
      <p v-if="ed.gpa" class="text-[9pt]" :class="INK">
        {{ t.gpa }} <span class="font-bold">{{ ed.gpa }}</span>
      </p>
      <BulletList :items="bullets(ed.achievements)" :ink-class="INK" />
    </div>
  </section>

  <section v-if="data.organizations?.length" class="mb-5">
    <PreviewSection
      :title="t.organizations"
      :ink-class="INK"
      :rule-class="RULE"
    />
    <div v-for="(o, i) in data.organizations" :key="i" class="mt-2 cv-entry">
      <EntryRow
        :title="o.organization || t.organization"
        :period="o.period"
        :ink-class="INK"
      />
      <p v-if="o.role" class="text-[9pt]" :class="INK">
        <span class="font-bold" :class="INK">{{ t.role }}</span>
        {{ o.role }}
      </p>
      <BulletList :items="bullets(o.description)" :ink-class="INK" />
    </div>
  </section>

  <section v-if="skillGroups.length" class="mb-5">
    <PreviewSection :title="t.skills" :ink-class="INK" :rule-class="RULE" />
    <p
      v-for="(g, i) in skillGroups"
      :key="g.key"
      class="text-[10pt] leading-relaxed"
      :class="[INK, i === 0 ? 'mt-2' : 'mt-1']"
    >
      <span class="font-bold">{{ g.label }}:</span>
      {{ g.items.join(" · ") }}
    </p>
  </section>

  <section v-if="data.projects?.length" class="mb-5">
    <PreviewSection :title="t.projects" :ink-class="INK" :rule-class="RULE" />
    <div v-for="(p, i) in data.projects" :key="i" class="mt-2 cv-entry">
      <p class="text-[10pt] font-bold" :class="INK">
        {{ p.title }}
        <a
          v-if="p.link"
          :href="hrefUrl(p.link)"
          target="_blank"
          rel="noopener"
          :aria-label="`Buka link proyek ${p.title}`"
          class="ml-1 inline-block align-baseline hover:underline"
          :class="INK"
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
      <p v-if="p.objective" class="text-[10pt]" :class="INK">
        {{ p.objective }}
      </p>
      <p v-if="p.role || p.techStack" class="text-[9pt]" :class="INK">
        <template v-if="p.role">
          <span class="font-bold" :class="INK">{{ t.role }}</span>
          {{ p.role }}
        </template>
        <span v-if="p.role && p.techStack"> · </span>
        <template v-if="p.techStack">
          <span class="font-bold" :class="INK">{{ t.techStack }}</span>
          {{ p.techStack }}
        </template>
      </p>
    </div>
  </section>

  <section v-if="data.certificates?.length" class="mb-5">
    <PreviewSection
      :title="t.certificates"
      :ink-class="INK"
      :rule-class="RULE"
    />
    <div v-for="(c, i) in data.certificates" :key="i" class="mt-2 cv-entry">
      <div class="flex items-baseline justify-between gap-4">
        <p class="text-[10pt]" :class="INK">
          {{ c.name }}
          <span class="font-bold" :class="INK">by {{ c.issuer }}</span>
        </p>
        <p class="shrink-0 text-[9pt] tabular-nums text-neutral-800">
          {{ c.year }}
        </p>
      </div>
      <p v-if="c.credentialId" class="text-[9pt]" :class="INK">
        <span class="font-bold" :class="INK">ID:</span>
        {{ c.credentialId }}
      </p>
    </div>
  </section>
  <section v-if="data.languages" class="mb-5">
    <PreviewSection :title="t.languages" :ink-class="INK" :rule-class="RULE" />
    <p class="mt-2 text-[10pt]" :class="INK">{{ data.languages }}</p>
  </section>
</template>
