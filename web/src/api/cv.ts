import type { Cv, CvData } from "@/types/cv";

async function csrf() {
  await fetch("/sanctum/csrf-cookie", { credentials: "include" });
}

function xsrfToken(): string {
  const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return m ? decodeURIComponent(m[1]!) : "";
}

/**
 * Terjemahkan respons jadi nilai atau lempar `Error` ber-`status`/`errors`.
 * Dipakai bersama oleh jalur JSON (`req`) dan multipart (`reqForm`) supaya
 * bentuk error tetap seragam di semua pemanggil.
 */
async function parseResponse<T>(res: Response): Promise<T> {
  if (res.status === 204) return undefined as T;
  const json = await res.json();
  if (!res.ok) {
    const err = new Error(json.message || "Request gagal") as Error & {
      status: number;
      errors?: unknown;
    };
    err.status = res.status;
    err.errors = json.errors;
    throw err;
  }
  return json as T;
}

async function req<T>(path: string, opts: RequestInit = {}): Promise<T> {
  const res = await fetch(path, {
    ...opts,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-XSRF-TOKEN": xsrfToken(),
      ...(opts.headers as Record<string, string>),
    },
    credentials: "include",
  });
  return parseResponse<T>(res);
}

/**
 * Jalur multipart. `Content-Type` sengaja TIDAK diset manual: browser harus
 * menulisnya sendiri agar `boundary` FormData ikut terkirim.
 */
async function reqForm<T>(path: string, body: FormData): Promise<T> {
  const res = await fetch(path, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "X-XSRF-TOKEN": xsrfToken(),
    },
    credentials: "include",
    body,
  });
  return parseResponse<T>(res);
}

export const cvApi = {
  list: () => req<{ data: Cv[] }>("/api/v1/cvs"),
  get: (id: number) => req<{ cv: Cv }>(`/api/v1/cvs/${id}`),
  create: (
    payload: {
      title: string;
      template: string;
      language: string;
      data: CvData;
    },
    draft = false,
  ) =>
    csrf().then(() =>
      req<{ cv: Cv }>(`/api/v1/cvs${draft ? "?draft=1" : ""}`, {
        method: "POST",
        body: JSON.stringify(payload),
      }),
    ),
  update: (
    id: number,
    payload: {
      title: string;
      template: string;
      language: string;
      data: CvData;
    },
    draft = false,
  ) =>
    csrf().then(() =>
      req<{ cv: Cv }>(`/api/v1/cvs/${id}${draft ? "?draft=1" : ""}`, {
        method: "PUT",
        body: JSON.stringify(payload),
      }),
    ),
  remove: (id: number) =>
    csrf().then(() => req<void>(`/api/v1/cvs/${id}`, { method: "DELETE" })),
  aiSummary: (
    cvId: number,
    language?: string,
    data?: CvData,
    jobDescription?: string,
  ) =>
    csrf().then(() =>
      req<{ summary: string }>("/api/v1/ai/summary", {
        method: "POST",
        body: JSON.stringify({
          cv_id: cvId,
          ...(language ? { language } : {}),
          ...(data ? { data } : {}),
          ...(jobDescription ? { job_description: jobDescription } : {}),
        }),
      }),
    ),
  /**
   * Unggah berkas CV (PDF/DOCX) untuk diekstrak + disusun AI jadi `CvData`.
   * Tidak menulis DB: hasil hanya dipakai mengisi form di klien.
   */
  parseCv: (file: File) =>
    csrf().then(() => {
      const form = new FormData();
      form.append("file", file);
      return reqForm<{ data: CvData; warnings: string[] }>(
        "/api/v1/ai/parse-cv",
        form,
      );
    }),
  translate: (cvId: number, target = "en") =>
    csrf().then(() =>
      req<{ data: CvData }>(`/api/v1/cvs/${cvId}/translate`, {
        method: "POST",
        body: JSON.stringify({ target }),
      }),
    ),
};
