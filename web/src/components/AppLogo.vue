<script setup lang="ts">
/**
 * AppLogo — wordmark "ResumeKan" berbasis gambar.
 *
 * Kenapa gambar, bukan teks? Wordmark punya "R" ber-kotak dan gradasi powder
 * pada "Kan" yang tidak bisa direplikasi dengan font sistem tanpa mengirim
 * font kustom. Gambar juga menjamin tampilan identik di semua perangkat.
 *
 * KENAPA DUA FILE (terang + gelap), bukan CSS filter:
 * filter: invert() membalik SELURUH kanal, sehingga powder #b0e0e6 ikut
 * terbalik jadi krem pucat dan gradasi rusak. Varian gelap di sini dibuat
 * dengan remap selektif (hanya piksel ink yang jadi terang), jadi powder
 * tetap utuh. Lihat docs/assets/ untuk sumbernya.
 *
 * PROPS
 * - height: TINGGI HURUF dalam px, bukan tinggi kanvas gambar.
 *           Aset sudah di-trim paddingnya sampai tipis (4px CSS), jadi angka
 *           ini bisa dibaca apa adanya: height 28 = huruf setinggi 28px.
 *           Sebelumnya aset masih membawa 47% ruang kosong sehingga height 28
 *           hanya menghasilkan huruf ~14.7px, setara teks navbar di sebelahnya.
 * - tone:   "auto"      -> ikut tema (pakai dark:), untuk latar paper/background
 *           "on-dark"   -> selalu varian gelap, untuk panel bg-ink permanen
 */
import logoLight from "@/assets/brand/logo-navbar.png";
import logoDark from "@/assets/brand/logo-navbar-dark.png";

withDefaults(
  defineProps<{
    height?: number;
    tone?: "auto" | "on-dark";
  }>(),
  { height: 28, tone: "auto" },
);
</script>

<template>
  <!-- Latar permanen gelap (panel bg-ink): pakai varian gelap saja -->
  <img
    v-if="tone === 'on-dark'"
    :src="logoDark"
    alt="ResumeKan"
    :style="{ height: `${height}px` }"
    class="w-auto select-none"
    draggable="false"
  />
  <!-- Latar ikut tema: swap aset, bukan filter warna -->
  <template v-else>
    <img
      :src="logoLight"
      alt="ResumeKan"
      :style="{ height: `${height}px` }"
      class="w-auto select-none dark:hidden"
      draggable="false"
    />
    <img
      :src="logoDark"
      alt=""
      aria-hidden="true"
      :style="{ height: `${height}px` }"
      class="hidden w-auto select-none dark:block"
      draggable="false"
    />
  </template>
</template>
