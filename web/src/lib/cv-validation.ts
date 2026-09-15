import type { CvData } from "@/types/cv";

/**
 * Aturan "wajib diisi" sisi klien — cerminan `StoreCvRequest` di backend.
 * Backend tetap sumber kebenaran; modul ini hanya untuk memberi umpan balik
 * lebih awal tanpa menunggu round-trip 422.
 */

/** Field wajib mutlak: selalu harus diisi, terlepas dari section lain. */
export interface RequiredField {
  /** Path datar, mis. `personal.name` — dipakai sebagai kunci error. */
  path: string;
  /** Label yang dibaca pengguna, mis. `Nama`. */
  label: string;
  /** Indeks step pemilik field — dipakai untuk auto-pindah saat submit gagal. */
  step: number;
  /** Ambil nilai dari CvData; string kosong / whitespace dianggap belum diisi. */
  get: (data: CvData) => string | undefined;
}

export const STEP_META = 0;
export const STEP_PERSONAL = 1;

export const REQUIRED_FIELDS: RequiredField[] = [
  {
    path: "title",
    label: "Judul CV",
    step: STEP_META,
    get: () => undefined, // diisi terpisah: title bukan bagian dari CvData
  },
  {
    path: "personal.name",
    label: "Nama",
    step: STEP_PERSONAL,
    get: (d) => d.personal?.name,
  },
  {
    path: "personal.email",
    label: "Email",
    step: STEP_PERSONAL,
    get: (d) => d.personal?.email,
  },
  {
    path: "personal.phone",
    label: "Telepon",
    step: STEP_PERSONAL,
    get: (d) => d.personal?.phone,
  },
  {
    path: "personal.address",
    label: "Alamat",
    step: STEP_PERSONAL,
    get: (d) => d.personal?.address,
  },
];

/** Field wajib di dalam entri berulang, per section. */
interface EntryRule {
  /** Kunci array di CvData. */
  key:
    | "experiences"
    | "education"
    | "organizations"
    | "certificates"
    | "projects";
  /** Indeks step pemilik section. */
  step: number;
  /** Nama section untuk pesan, mis. `Pengalaman`. */
  sectionLabel: string;
  /** Field wajib di tiap entri: nama properti -> label. */
  fields: Record<string, string>;
}

const ENTRY_RULES: EntryRule[] = [
  {
    key: "experiences",
    step: 3,
    sectionLabel: "Pengalaman",
    fields: {
      company: "Perusahaan",
      position: "Posisi",
      startDate: "Tanggal mulai",
      endDate: "Tanggal selesai",
    },
  },
  {
    key: "education",
    step: 4,
    sectionLabel: "Pendidikan",
    fields: {
      institution: "Institusi",
      degree: "Gelar & jurusan",
      year: "Tahun",
    },
  },
  {
    key: "organizations",
    step: 5,
    sectionLabel: "Organisasi",
    fields: { organization: "Organisasi", role: "Peran", period: "Periode" },
  },
  {
    key: "certificates",
    step: 8,
    sectionLabel: "Sertifikat",
    fields: {
      name: "Nama sertifikat",
      issuer: "Penerbit",
      year: "Tahun terbit",
    },
  },
  {
    key: "projects",
    step: 7,
    sectionLabel: "Proyek",
    fields: { title: "Nama proyek", role: "Peran" },
  },
];

function blank(v: unknown): boolean {
  return typeof v !== "string" || v.trim() === "";
}

/**
 * Buang entri yang SELURUH field wajibnya kosong.
 *
 * "Klik + Tambah lalu batal" adalah niat yang sah, bukan kesalahan, jadi entri
 * kosong tidak boleh memblokir simpan — cukup diabaikan. Entri yang terisi
 * sebagian tetap dipertahankan dan akan memunculkan error inline, karena di
 * situ pengguna jelas berniat mengisi.
 *
 * Mengembalikan CvData baru (tidak memutasi input) plus jumlah yang dibuang.
 */
export function pruneEmptyEntries(data: CvData): {
  data: CvData;
  removed: number;
} {
  // `data` bisa berupa proxy reaktif Vue, yang tidak bisa di-`structuredClone`.
  // Ambil snapshot biasa lewat JSON — bentuk CvData murni JSON.
  const next = JSON.parse(JSON.stringify(data)) as CvData;
  let removed = 0;

  for (const rule of ENTRY_RULES) {
    const list = next[rule.key] as Record<string, unknown>[] | undefined;
    if (!Array.isArray(list)) continue;

    const kept = list.filter((entry) => {
      const isBlank = Object.keys(rule.fields).every((f) => blank(entry[f]));
      return !isBlank;
    });

    removed += list.length - kept.length;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (next as any)[rule.key] = kept;
  }

  return { data: next, removed };
}

/**
 * Kumpulkan semua field yang belum diisi, urut naik berdasarkan step.
 *
 * `title` dikirim terpisah karena bukan bagian dari CvData.
 */
export function collectMissing(
  data: CvData,
  title: string,
): { path: string; label: string; step: number }[] {
  const missing: { path: string; label: string; step: number }[] = [];

  for (const f of REQUIRED_FIELDS) {
    const value = f.path === "title" ? title : f.get(data);
    if (blank(value))
      missing.push({ path: f.path, label: f.label, step: f.step });
  }

  for (const rule of ENTRY_RULES) {
    const list = data[rule.key] as Record<string, unknown>[] | undefined;
    if (!Array.isArray(list)) continue;

    list.forEach((entry, i) => {
      for (const [field, label] of Object.entries(rule.fields)) {
        if (blank(entry[field])) {
          missing.push({
            path: `${rule.key}.${i}.${field}`,
            label: `${label} (${rule.sectionLabel} #${i + 1})`,
            step: rule.step,
          });
        }
      }
    });
  }

  return missing.sort((a, b) => a.step - b.step);
}

/** Nama field untuk pesan, mis. `Judul CV`. */
export function messageFor(label: string): string {
  return `${label} wajib diisi.`;
}

/**
 * Petakan payload 422 Laravel ke kunci error sisi klien.
 *
 * Laravel memakai `data.certificates.0.name`; UI memakai path yang sama tanpa
 * awalan `data.` supaya cocok dengan `collectMissing`. Field yang tidak
 * dikenali tetap diteruskan agar tidak hilang diam-diam.
 */
export function mapServerErrors(errors: unknown): Record<string, string> {
  const out: Record<string, string> = {};
  if (!errors || typeof errors !== "object") return out;

  for (const [key, val] of Object.entries(errors as Record<string, unknown>)) {
    const msg = Array.isArray(val) ? String(val[0]) : String(val);
    out[key.startsWith("data.") ? key.slice(5) : key] = msg;
  }

  return out;
}
