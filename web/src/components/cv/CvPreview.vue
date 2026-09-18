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
import { getTemplateConfig, CV_FONTS, CV_FONT_SIZES } from "@/lib/cv-templates";
import type { CvFontSizeId } from "@/lib/cv-templates";
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
    zoom?: number | "fit";
    fontFamily?: string;
    fontSize?: string;
  }>(),
  {
    compact: false,
    language: "id",
    paged: false,
    zoom: "fit",
    fontFamily: "system-sans",
    fontSize: "default",
  },
);

const emit = defineEmits<{
  (
    e: "scaleChange",
    val: { scale: number; fitScale: number; isFit: boolean },
  ): void;
}>();

const normal = computed(() => normalizeCvData({ ...props.data }));
const tpl = computed(() => getTemplateConfig(props.template));
const comp = computed(() =>
  tpl.value.id === "neon"
    ? CvNeon
    : tpl.value.id === "classic"
      ? CvClassic
      : CvModern,
);

const resolvedFont = computed(
  () => CV_FONTS.find((f) => f.id === props.fontFamily) ?? CV_FONTS[0]!,
);
const isCustomFont = computed(() => resolvedFont.value.googleFamily !== null);
const fontFamilyStyle = computed(() =>
  isCustomFont.value ? resolvedFont.value.family : undefined,
);
const fontClass = computed(() =>
  isCustomFont.value ? undefined : tpl.value.font,
);
const sizeClass = computed(() => `cv-size-${props.fontSize || "default"}`);
const googleFontUrl = computed(() => {
  const gf = resolvedFont.value.googleFamily;
  if (!gf) return null;
  return `https://fonts.googleapis.com/css2?family=${gf}:wght@400;600;700&display=swap`;
});
const resolvedFontSize = computed(() => {
  return (
    CV_FONT_SIZES.find((s) => s.id === (props.fontSize as CvFontSizeId)) ??
    CV_FONT_SIZES[1]!
  );
});

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
const fitScale = ref(1);
const wrapperHeight = ref("auto");
const wrapperWidth = ref("auto");
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
  // Wrapper diukur presisi mengikuti skala agar container dapat scroll rapi saat zoom.
  const sheets = pageStarts.value.length;
  const layoutHeight = sheets * A4_H + Math.max(0, sheets - 1) * 16;
  wrapperHeight.value = Math.ceil(layoutHeight * scale.value) + "px";
  wrapperWidth.value = Math.ceil(A4_W * scale.value) + "px";
}

function updateScale() {
  if (!pagedWrapRef.value) return;
  const parent = pagedWrapRef.value.parentElement;
  const available = parent?.clientWidth ?? A4_W;
  // Margin visual nyaman agar kertas dokumen memiliki ruang napas dan tidak terlalu zoom
  const margin = available > 500 ? 48 : 24;
  const targetAvailable = Math.max(160, available - margin);
  fitScale.value = Math.min(0.85, Math.max(0.2, targetAvailable / A4_W));
  const isFit = props.zoom === "fit" || props.zoom == null;
  scale.value = isFit ? fitScale.value : Math.max(0.2, props.zoom / 100);
  updateWrapperSize();
  emit("scaleChange", {
    scale: scale.value,
    fitScale: fitScale.value,
    isFit,
  });
}

watch(
  () => [
    props.data,
    props.template,
    props.language,
    props.fontFamily,
    props.fontSize,
  ],
  () => {
    nextTick(() => setTimeout(remeasure, 50));
    if (typeof document !== "undefined" && document.fonts) {
      document.fonts.ready.then(() => nextTick(remeasure));
    }
  },
  { deep: true },
);

watch(
  () => props.zoom,
  () => {
    updateScale();
  },
);

onMounted(() => {
  if (props.paged) {
    const parent = pagedWrapRef.value?.parentElement;
    if (parent) {
      ro = new ResizeObserver(() => updateScale());
      ro.observe(parent);
    } else if (pagedWrapRef.value) {
      ro = new ResizeObserver(() => updateScale());
      ro.observe(pagedWrapRef.value);
    }
    nextTick(remeasure);
  }
});

onBeforeUnmount(() => ro?.disconnect());
</script>

<template>
  <Teleport to="head">
    <link v-if="googleFontUrl" rel="stylesheet" :href="googleFontUrl" />
  </Teleport>

  <!-- Non-paged: continous scroll (HomeView, print shell) -->
  <div
    v-if="!paged"
    :id="`cv-preview-${tpl.id}`"
    :class="[
      'cv-paper mx-auto w-full max-w-[800px] bg-white text-slate-900 antialiased',
      fontClass,
      sizeClass,
    ]"
    :style="{
      lineHeight: 1.5,
      fontFamily: fontFamilyStyle,
    }"
  >
    <div :class="['cv-page', compact ? 'px-0 py-4' : 'px-8 py-8 sm:px-10']">
      <component :is="comp" :data="normal" :language="language" />
    </div>
  </div>

  <!-- Paged: multi-halaman identik download PDF (CvFormView) -->
  <div
    v-else
    ref="pagedWrapRef"
    class="paged-preview-wrapper mx-auto"
    :style="{ width: wrapperWidth, height: wrapperHeight }"
  >
    <!-- Hidden measure container → lebar konten identik print -->
    <div
      ref="measureRef"
      :class="[fontClass, sizeClass]"
      :style="{
        position: 'fixed',
        left: '-99999px',
        top: '0',
        width: '673px',
        lineHeight: '1.5',
        zIndex: '-1',
        pointerEvents: 'none',
        fontFamily: fontFamilyStyle,
      }"
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
      class="paged-stack"
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
              :class="['a4-page-inner', fontClass, sizeClass]"
              :style="{
                width: '673px',
                lineHeight: '1.5',
                fontFamily: fontFamilyStyle,
              }"
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
    0 4px 6px -1px rgba(15, 23, 42, 0.08),
    0 2px 4px -2px rgba(15, 23, 42, 0.06),
    0 0 0 1px rgba(15, 23, 42, 0.08);
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
