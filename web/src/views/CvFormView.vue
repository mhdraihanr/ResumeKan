<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useCvStore } from "@/stores/cv";
import { emptyCvData, normalizeCvData } from "@/types/cv";
import type { CvData } from "@/types/cv";
import CvForm from "@/components/cv/CvForm.vue";
import CvPreview from "@/components/cv/CvPreview.vue";

const props = defineProps<{ id?: string }>();
const router = useRouter();
const cvStore = useCvStore();
const win = window;

const isEdit = computed(() => !!cvId.value);
const cvId = ref<number | undefined>(props.id ? Number(props.id) : undefined);
const title = ref("");
const template = ref("modern");
const language = ref("id");
const data = ref<CvData>(emptyCvData());
const error = ref<string | null>(null);
const saving = ref(false);
const drafting = ref(false);
const formRef = ref<InstanceType<typeof CvForm> | null>(null);
const toast = ref<{ msg: string; ok: boolean } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;

function showToast(msg: string, ok = true) {
  toast.value = { msg, ok };
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toast.value = null), 2600);
}

/** Ringkasan entri kosong yang dibuang saat submit, mis. `2 entri kosong diabaikan`. */
const removedNote = ref("");
let removedTimer: ReturnType<typeof setTimeout> | undefined;

function onRemovedEntries(count: number) {
  clearTimeout(removedTimer);
  if (count === 0) {
    removedNote.value = "";
    return;
  }
  removedNote.value = `${count} entri kosong diabaikan`;
  removedTimer = setTimeout(() => (removedNote.value = ""), 4000);
}

onMounted(async () => {
  if (isEdit.value && cvId.value) {
    await cvStore.fetchOne(cvId.value);
    if (cvStore.current) {
      title.value = cvStore.current.title;
      template.value = cvStore.current.template;
      language.value = cvStore.current.language;
      data.value = normalizeCvData(cvStore.current.data ?? emptyCvData());
    }
  }
});

async function submit() {
  error.value = null;

  // Validasi klien dulu; kalau ada yang kurang, `CvForm` menampilkan error
  // inline dan memindahkan pengguna ke step yang bermasalah.
  if (!(await formRef.value?.prepareSubmit())) return;

  saving.value = true;
  try {
    const payload = {
      title: title.value,
      template: template.value,
      language: language.value,
      data: data.value,
    };
    if (cvId.value) await cvStore.update(cvId.value, payload);
    else await cvStore.create(payload);
    router.push("/dashboard");
  } catch (e) {
    const err = e as { status?: number; message?: string; errors?: unknown };
    if (err.status === 422 && err.errors) {
      // Field-level: petakan ke error inline, bukan tampilkan pesan mentah.
      await formRef.value?.applyServerErrors(err.errors);
    } else {
      // Kegagalan operasional (network/500) — pesan mentah berguna untuk debug.
      error.value = err.message ?? "Gagal menyimpan CV";
    }
  } finally {
    saving.value = false;
  }
}

async function draftSave() {
  error.value = null;

  // Buang entri kosong lebih dulu (klik "+ Tambah" lalu batal bukan kesalahan),
  // supaya draft tidak gagal hanya karena entri setengah jadi.
  await formRef.value?.pruneEntries();

  // Draft boleh kurang isi, tapi FORMAT (email/telepon) tetap harus benar.
  // Dicek di klien agar error inline muncul tanpa round-trip 422 mentah.
  if (!(await formRef.value?.checkFormats())) {
    showToast("Perbaiki dulu isian yang salah format.", false);
    return;
  }

  drafting.value = true;
  try {
    const payload = {
      title: title.value,
      template: template.value,
      language: language.value,
      data: data.value,
    };
    if (cvId.value) {
      await cvStore.update(cvId.value, payload, true);
    } else {
      const cv = await cvStore.create(payload, true);
      cvId.value = cv.id;
    }
    showToast("Draft tersimpan");
  } catch (e) {
    const err = e as { status?: number; message?: string; errors?: unknown };
    if (err.status === 422 && err.errors) {
      // Server menolak; petakan ke error inline, bukan tampilkan pesan mentah.
      await formRef.value?.applyServerErrors(err.errors);
      showToast("Ada isian yang perlu diperbaiki. Cek penanda merah.", false);
    } else {
      showToast("Gagal menyimpan draft. Coba lagi.", false);
    }
  } finally {
    drafting.value = false;
  }
}

/**
 * Unduh PDF hanya untuk CV yang sudah lengkap. Kelengkapan dicek SINKRON lewat
 * `isComplete()` dulu, jadi window tidak pernah dibuka saat data kurang —
 * pengguna tidak melihat tab berkelip terbuka lalu tertutup.
 *
 * Kalau kurang: jalankan `prepareSubmit()` agar error inline muncul dan
 * pengguna dipindah ke step bermasalah, lalu tampilkan toast.
 *
 * Setelah lolos, `win.open` dipanggil TANPA `await` sebelumnya agar tetap
 * dianggap user-gesture dan tidak kena popup-blocker. Server punya guard
 * kelengkapan sendiri sebagai pertahanan berlapis untuk pemanggil langsung
 * (mis. tombol PDF di Dashboard), tetapi jalur editor sudah dijamin klien.
 */
async function downloadPdf() {
  if (!formRef.value?.isComplete()) {
    await formRef.value?.prepareSubmit();
    showToast("Lengkapi dulu sebelum mengunduh.", false);
    return;
  }
  win.open(`/api/v1/cvs/${cvId.value}/pdf`, "_blank");
}
</script>

<template>
  <main class="min-h-screen bg-slate-50 dark:bg-background">
    <div class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6">
      <!-- Wrapper bersama agar sticky bar selebar form+preview -->
      <div class="mx-auto w-full lg:max-w-[1220px] xl:max-w-[1260px]">
        <!-- Sticky header: judul + aksi selalu terlihat saat scroll -->
        <div
          class="sticky top-0 z-20 mb-4 flex items-center gap-3 rounded-xl border border-slate-200 bg-white/95 px-4 py-2.5 shadow-sm backdrop-blur dark:border-border dark:bg-secondary-background/95"
        >
          <button
            @click="router.push('/dashboard')"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
          >
            ← Kembali
          </button>
          <h1 class="text-xl font-bold text-slate-900 dark:text-foreground">
            {{ isEdit ? "Edit CV" : "Buat CV Baru" }}
          </h1>
          <div class="ml-auto flex items-center gap-3">
            <span
              class="hidden text-xs text-slate-400 dark:text-foreground/60 sm:inline"
            >
              Preview update otomatis saat mengetik</span
            >
            <button
              @click="draftSave"
              :disabled="drafting"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-40 dark:border-border dark:bg-secondary-background dark:text-foreground/70 dark:hover:bg-white/15 dark:hover:text-foreground"
            >
              {{ drafting ? "Menyimpan..." : "Simpan Draft" }}
            </button>
            <button
              v-if="isEdit"
              @click="downloadPdf"
              class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 dark:bg-main dark:hover:bg-blue-700"
            >
              Download PDF
            </button>
          </div>
        </div>

        <div
          class="grid gap-5 lg:grid-cols-[480px_minmax(0,720px)] xl:grid-cols-[520px_minmax(0,720px)]"
        >
          <!-- Form -->
          <div
            class="rounded-2xl bg-white p-5 shadow-sm sm:p-6 lg:max-h-[calc(100vh-6rem)] lg:overflow-auto dark:bg-secondary-background"
          >
            <p
              v-if="error"
              class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300"
            >
              {{ error }}
            </p>
            <CvForm
              ref="formRef"
              v-model="data"
              v-model:title="title"
              v-model:template="template"
              v-model:language="language"
              :cv-id="cvId"
              @submit="submit"
              @update:removed-entries="onRemovedEntries"
            />
            <p
              v-if="saving"
              class="mt-3 text-center text-xs text-slate-400 dark:text-foreground/60"
            >
              Menyimpan...
            </p>
            <p
              v-if="removedNote"
              class="mt-3 text-center text-xs text-slate-500 dark:text-foreground/60"
            >
              {{ removedNote }}
            </p>
          </div>

          <!-- Preview -->
          <div
            class="lg:sticky lg:top-6 lg:max-h-[calc(100vh-6rem)] lg:overflow-auto"
          >
            <div class="mb-2 flex items-center justify-between">
              <span
                class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-foreground/70"
                >Preview · {{ template }}</span
              >
            </div>
            <div
              class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-border dark:bg-secondary-background"
            >
              <CvPreview
                :data="data"
                :template="template"
                :language="language"
                paged
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--
      Live region tetap ada di DOM (role berubah, bukan elemennya yang
      dimunculkan) supaya screen reader mengumumkan perubahan teks dengan andal.
    -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-1"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        :role="toast && !toast.ok ? 'alert' : 'status'"
        :aria-live="toast && !toast.ok ? 'assertive' : 'polite'"
        :aria-atomic="true"
        class="fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-lg"
        :class="
          toast && !toast.ok
            ? 'bg-red-600 dark:border dark:border-red-400/60 dark:bg-red-900'
            : 'bg-slate-900 dark:border dark:border-border dark:bg-secondary-background dark:text-foreground'
        "
      >
        {{ toast?.msg ?? "" }}
      </div>
    </transition>
  </main>
</template>
