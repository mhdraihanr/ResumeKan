import type { Cv, CvData } from "@/types/cv";

async function csrf() {
  await fetch("/sanctum/csrf-cookie", { credentials: "include" });
}

function xsrfToken(): string {
  const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return m ? decodeURIComponent(m[1]!) : "";
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

export const cvApi = {
  list: () => req<{ data: Cv[] }>("/api/v1/cvs"),
  get: (id: number) => req<{ cv: Cv }>(`/api/v1/cvs/${id}`),
  create: (payload: {
    title: string;
    template: string;
    language: string;
    data: CvData;
  }) =>
    csrf().then(() =>
      req<{ cv: Cv }>("/api/v1/cvs", {
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
  ) =>
    csrf().then(() =>
      req<{ cv: Cv }>(`/api/v1/cvs/${id}`, {
        method: "PUT",
        body: JSON.stringify(payload),
      }),
    ),
  remove: (id: number) =>
    csrf().then(() => req<void>(`/api/v1/cvs/${id}`, { method: "DELETE" })),
  aiSummary: (cvId: number, language?: string, data?: CvData) =>
    csrf().then(() =>
      req<{ summary: string }>("/api/v1/ai/summary", {
        method: "POST",
        body: JSON.stringify({
          cv_id: cvId,
          ...(language ? { language } : {}),
          ...(data ? { data } : {}),
        }),
      }),
    ),
};
