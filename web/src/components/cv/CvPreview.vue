<script setup lang="ts">
import {
  ref,
  computed,
  watch,
  nextTick,
  onMounted,
  onBeforeUnmount,
} from "vue";
import type { CvData } from "@/types/cv";
import { normalizeCvData } from "@/types/cv";
import { getTemplateConfig } from "@/lib/cv-templates";
import CvModern from "./templates/CvModern.vue";
import CvClassic from "./templates/CvClassic.vue";
import CvNeon from "./templates/CvNeon.vue";

const props = withDefaults(
  defineProps<{
    data: CvData;
    template: string;
    language?: string;
    compact?: boolean;
    paged?: boolean;
  }>(),
  { compact: false, language: "id", paged: false },
);

const normal = computed(() => normalizeCvData({ ...props.data }));
const tpl = computed(() => getTemplateConfig(props.template));
const comp = computed(() =>
  tpl.value.id === "neon"
    ? CvNeon
    : tpl.value.id === "classic"
      ? CvClassic
      : CvModern,
);

// --- Paged mode (preview multi-halaman identik PDF) ---
// Print layout: @page margin 14mm(top/bottom) + 16mm(left/right) pada A4 (210×297mm)
// Kertas A4 @96dpi: 794×1123px; area konten: 178mm×269mm = 673×1017px
const A4_W = 794;
const A4_H = 1123;
const CONTENT_WIDTH = 673;
const PAGE_HEIGHT = 1017;
const MARGIN_X = Math.round((A4_W - CONTENT_WIDTH) / 2); // ~61px = 16mm
const MARGIN_Y = Math.round((A4_H - PAGE_HEIGHT) / 2); // ~53px = 14mm

const measureRef = ref<HTMLElement | null>(null);
const pagedWrapRef = ref<HTMLElement | null>(null);
const pageStarts = ref<number[]>([0]);
const measureTotal = ref(0);
const scale = ref(1);
const wrapperHeight = ref("auto");
const wrapperWidth = ref("100%");
let ro: ResizeObserver | null = null;

// Section (atau header) adalah blok atomik yang tidak boleh terpotong antar
// halaman, sama seperti `break-inside: avoid` pada print CSS. Preview memecah
// hanya di batas antar blok ini supaya identik dengan hasil PDF.
function remeasure() {
  if (!props.paged || !measureRef.value) return;
  const page = measureRef.value.querySelector(".cv-page") as HTMLElement | null;
  if (!page) return;

  const blocks: { top: number; bottom: number }[] = [];
  page
    .querySelectorAll<HTMLElement>(":scope > header, :scope > section")
    .forEach((el) => {
      const top = el.offsetTop;
      blocks.push({ top, bottom: top + el.offsetHeight });
    });
  const total = page.scrollHeight;
  measureTotal.value = total;

  // Greedy: isi halaman dengan blok selama muat penuh, dorong blok yang tidak
  // muat ke halaman berikutnya. Hasilnya tidak ada blok terpotong di tengah.
  const starts: number[] = [0];
  let pageTop = 0;
  for (const b of blocks) {
    if (b.bottom - pageTop > PAGE_HEIGHT + 1 && b.top - pageTop > 1) {
      starts.push(b.top);
      pageTop = b.top;
    }
  }
  // Kalau masih ada sisa konten melebihi kapasitas halaman terakhir (blok lebih
  // tinggi dari satu halaman), potong keras di batas halaman agar tidak hilang.
  const last = starts[starts.length - 1] ?? 0;
  if (total - last > PAGE_HEIGHT) {
    starts.push(last + PAGE_HEIGHT);
  }
  pageStarts.value = starts;
  updateWrapperSize();
}

// Tinggi konten yang benar-benar ditampilkan tiap lembar: dari awal halaman ini
// sampai awal halaman berikutnya (atau sampai akhir konten utk halaman terakhir).
const pageHeights = computed(() =>
  pageStarts.value.map((s, i) => {
    const end =
      i < pageStarts.value.length - 1
        ? (pageStarts.value[i + 1] ?? measureTotal.value)
        : measureTotal.value;
    return Math.min(PAGE_HEIGHT, Math.max(1, end - s));
  }),
);

function updateWrapperSize() {
  // Tinggi layout = N lembar × tinggi A4 + gap 16px antar lembar.
  // Wrapper melebar penuh kartu, sheet di-center via transform-origin top left
  const sheets = pageStarts.value.length;
  const layoutHeight = sheets * A4_H + Math.max(0, sheets - 1) * 16;
  wrapperHeight.value = Math.ceil(layoutHeight * scale.value) + "px";
  // Width = 100% agar memenuhi kartu, content tetap di-scale via paged-stack
}

function updateScale() {
  if (!pagedWrapRef.value) return;
  const available = pagedWrapRef.value.parentElement?.clientWidth ?? A4_W;
  scale.value = Math.min(1, available / A4_W);
  updateWrapperSize();
}

watch(
  () => [props.data, props.template, props.language],
  () => {
    nextTick(() => setTimeout(remeasure, 50));
  },
  { deep: true },
);

onMounted(() => {
  if (props.paged) {
    ro = new ResizeObserver(updateScale);
    if (pagedWrapRef.value) ro.observe(pagedWrapRef.value);
    nextTick(remeasure);
  }
});

onBeforeUnmount(() => ro?.disconnect());
</script>

<template>
  <!-- Non-paged: continous scroll (HomeView, print shell) -->
  <div
    v-if="!paged"
    :id="`cv-preview-${tpl.id}`"
    :class="[
      'cv-paper mx-auto w-full max-w-[800px] bg-white text-slate-900 antialiased',
      tpl.font,
    ]"
    style="font-size: 11pt; line-height: 1.5"
  >
    <div :class="['cv-page', compact ? 'px-0 py-4' : 'px-8 py-8 sm:px-10']">
      <component :is="comp" :data="normal" :language="language" />
    </div>
  </div>

  <!-- Paged: multi-halaman identik download PDF (CvFormView) -->
  <div
    v-else
    ref="pagedWrapRef"
    class="paged-preview-wrapper"
    :style="{ width: wrapperWidth, height: wrapperHeight }"
  >
    <!-- Hidden measure container → lebar konten identik print -->
    <div
      ref="measureRef"
      :class="[tpl.font]"
      style="
        position: fixed;
        left: -99999px;
        top: 0;
        width: 673px;
        font-size: 11pt;
        line-height: 1.5;
        z-index: -1;
        pointer-events: none;
      "
    >
      <div
        class="cv-page bg-white text-slate-900 antialiased"
        style="padding: 0"
      >
        <component :is="comp" :data="normal" :language="language" />
      </div>
    </div>

    <!-- Lembar kertas A4 (dengan margin putih) tersusun vertikal, di-scale agar muat panel -->
    <div
      class="paged-stack mx-auto"
      :style="{
        transform: `scale(${scale})`,
        transformOrigin: 'top left',
        width: A4_W + 'px',
      }"
    >
      <div
        v-for="(start, n) in pageStarts"
        :key="n"
        class="a4-sheet"
        :style="{
          width: A4_W + 'px',
          height: A4_H + 'px',
          padding: `${MARGIN_Y}px ${MARGIN_X}px`,
          marginBottom: n < pageStarts.length - 1 ? '16px' : '0',
        }"
      >
        <div
          class="a4-clip"
          :style="{
            width: CONTENT_WIDTH + 'px',
            height: pageHeights[n] + 'px',
          }"
        >
          <div :style="{ transform: `translateY(-${start}px)` }">
            <div
              :class="['a4-page-inner', tpl.font]"
              style="width: 673px; font-size: 11pt; line-height: 1.5"
            >
              <div
                class="cv-page bg-white text-slate-900 antialiased"
                style="padding: 0"
              >
                <component :is="comp" :data="normal" :language="language" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
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
  /*
    Netralkan hanya link tanpa class warna (mis. link di teks user), supaya biru
    default browser tidak ikut tercetak. Link template CV punya class warna
    sendiri (ink netral, lihat DESIGN.md 7) sehingga harus dibiarkan menang.
    Tanpa :not([class]), `color: inherit !important` akan mengalahkan class
    Tailwind apa pun dan link jatuh ke warna parent (slate-600), jadi preview
    di layar tidak pernah sama dengan PDF.
  */
  a:not([class]) {
    color: inherit;
    text-decoration: none;
  }
  /* PDF tidak memotong elemen: tiap section/header pindah utuh ke halaman baru */
  header,
  section {
    break-inside: avoid;
    page-break-inside: avoid;
  }
}

/* Paged preview: tiap lembar terlihat seperti kertas A4 mini */
.paged-preview-wrapper {
  overflow: hidden;
}

.a4-sheet {
  position: relative;
  overflow: hidden;
  background: #fff;
  border-radius: 4px;
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.12),
    0 1px 2px rgba(0, 0, 0, 0.06);
}

.a4-clip {
  position: relative;
  overflow: hidden;
}

.a4-clip > div {
  transform-origin: top left;
}

.a4-page-inner {
  color: #0f172a;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
