import { defineStore } from "pinia";
import { ref } from "vue";
import { cvApi } from "@/api/cv";
import type { Cv, CvData } from "@/types/cv";

export const useCvStore = defineStore("cv", () => {
  const list = ref<Cv[]>([]);
  const current = ref<Cv | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchList() {
    loading.value = true;
    error.value = null;
    try {
      const res = await cvApi.list();
      list.value = res.data;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Gagal memuat CV";
    } finally {
      loading.value = false;
    }
  }

  async function fetchOne(id: number) {
    loading.value = true;
    error.value = null;
    try {
      const res = await cvApi.get(id);
      current.value = res.cv;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Gagal memuat CV";
    } finally {
      loading.value = false;
    }
  }

  async function create(payload: {
    title: string;
    template: string;
    language: string;
    data: CvData;
  }) {
    loading.value = true;
    error.value = null;
    try {
      const res = await cvApi.create(payload);
      list.value.unshift(res.cv);
      return res.cv;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Gagal membuat CV";
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function update(
    id: number,
    payload: {
      title: string;
      template: string;
      language: string;
      data: CvData;
    },
  ) {
    loading.value = true;
    error.value = null;
    try {
      const res = await cvApi.update(id, payload);
      const idx = list.value.findIndex((c) => c.id === id);
      if (idx !== -1) list.value[idx] = res.cv;
      current.value = res.cv;
      return res.cv;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Gagal menyimpan CV";
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function remove(id: number) {
    await cvApi.remove(id);
    list.value = list.value.filter((c) => c.id !== id);
  }

  return {
    list,
    current,
    loading,
    error,
    fetchList,
    fetchOne,
    create,
    update,
    remove,
  };
});
