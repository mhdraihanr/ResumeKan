<script setup lang="ts">
import { AlertDialogCancel } from "reka-ui";
import {
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogOverlay,
  AlertDialogPortal,
  AlertDialogRoot,
  AlertDialogTitle,
} from "reka-ui";
import { computed } from "vue";
import { Loader2, TriangleAlert } from "lucide-vue-next";
// Aksi destruktif: fokus awal jatuh ke Batal (reka-ui), Esc = Batal,
// dan fokus dikembalikan ke pemicu saat dialog ditutup.
const props = withDefaults(
  defineProps<{
    open: boolean;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    pending?: boolean;
    pendingLabel?: string;
    /** `danger` untuk data yang hilang permanen, `neutral` untuk konfirmasi biasa. */
    tone?: "danger" | "neutral";
  }>(),
  {
    cancelLabel: "Batal",
    pending: false,
    pendingLabel: "Memproses...",
    tone: "danger",
    confirmLabel: "Ya, lanjutkan",
  },
);

const iconClass = computed(() =>
  props.tone === "danger"
    ? "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300"
    : "bg-powder text-ink dark:bg-white/10 dark:text-foreground",
);

const confirmClass = computed(() =>
  props.tone === "danger"
    ? "bg-red-700 text-white hover:bg-red-800 dark:hover:bg-red-600"
    : "bg-navy text-white hover:bg-navy/90 dark:bg-main dark:hover:bg-main/90",
);

const emit = defineEmits<{
  "update:open": [value: boolean];
  confirm: [];
  cancel: [];
}>();

function close() {
  emit("update:open", false);
}

function onCancel() {
  emit("cancel");
  close();
}

// Bukan `AlertDialogAction`: komponen itu menutup dialog tanpa meneruskan handler.
function onConfirm() {
  if (props.pending) return;
  emit("confirm");
}

// Esc ditangani sendiri di dalam konten: listener reka-ui di layer luar tidak
// selalu menerima keydown saat fokus berada di tombol konten.
function onEscape(event: KeyboardEvent) {
  if (props.pending) {
    event.preventDefault();
    return;
  }
  onCancel();
}
</script>

<template>
  <AlertDialogRoot :open="open" @update:open="emit('update:open', $event)">
    <AlertDialogPortal>
      <AlertDialogOverlay
        class="fixed inset-0 z-50 bg-ink/60 backdrop-blur-[1px] dark:bg-black/70"
      />
      <AlertDialogContent
        class="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-base border-2 border-ink bg-paper p-5 shadow-shadow focus:outline-none dark:border-border dark:bg-secondary-background"
        @escape-key-down="onEscape"
      >
        <div class="flex items-start gap-3">
          <span
            class="grid size-9 shrink-0 place-items-center rounded-base border-2 border-ink dark:border-border"
            :class="iconClass"
            aria-hidden="true"
          >
            <TriangleAlert class="size-4" />
          </span>
          <div class="min-w-0 flex-1">
            <AlertDialogTitle
              class="text-base font-black leading-snug text-ink dark:text-foreground"
            >
              {{ title }}
            </AlertDialogTitle>
            <AlertDialogDescription
              v-if="description"
              class="mt-1.5 text-sm leading-relaxed text-ink/80 dark:text-foreground/80"
            >
              {{ description }}
            </AlertDialogDescription>
          </div>
        </div>

        <div class="mt-5 flex flex-wrap justify-end gap-2">
          <AlertDialogCancel
            class="inline-flex h-9 items-center justify-center rounded-base border-2 border-ink bg-white px-3.5 text-xs font-bold text-ink transition-colors hover:bg-powder focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-white/15 dark:focus-visible:outline-foreground"
            @click="onCancel"
          >
            {{ cancelLabel }}
          </AlertDialogCancel>
          <button
            type="button"
            :disabled="pending"
            :aria-busy="pending ? 'true' : 'false'"
            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-base border-2 border-ink px-3.5 text-xs font-bold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink disabled:cursor-wait disabled:opacity-80 dark:border-border dark:focus-visible:outline-foreground"
            :class="confirmClass"
            @click="onConfirm"
          >
            <Loader2
              v-if="pending"
              class="size-4 animate-spin"
              aria-hidden="true"
            />
            {{ pending ? pendingLabel : confirmLabel }}
          </button>
        </div>
      </AlertDialogContent>
    </AlertDialogPortal>
  </AlertDialogRoot>
</template>
