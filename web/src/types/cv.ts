export interface CvData {
  fontFamily?: string;
  fontSize?: string;
  personal: {
    name: string;
    email: string;
    phone: string;
    address: string;
    linkedin?: string;
    website?: string;
    github?: string;
    photo?: string;
  };
  summary?: string;
  experiences?: {
    company: string;
    position: string;
    location?: string;
    employmentType?: string;
    startDate: string;
    endDate: string;
    description?: string;
  }[];
  education?: {
    institution: string;
    degree: string;
    location?: string;
    year: string;
    gpa?: string;
    achievements?: string;
  }[];
  organizations?: {
    organization: string;
    role: string;
    period: string;
    description?: string;
  }[];
  skills?: { hard?: string; soft?: string };
  languages?: string;
  certificates?: {
    name: string;
    issuer: string;
    year: string;
    credentialId?: string;
  }[];
  projects?: {
    title: string;
    role: string;
    objective?: string;
    techStack?: string;
    link?: string;
  }[];
}

export interface Cv {
  id: number;
  title: string;
  template: "modern" | "classic" | "neon";
  language: "id" | "en";
  data?: CvData;
  updated_at: string;
  created_at?: string;
  /** Dari server: CV sudah lengkap & siap diunduh PDF (untuk disable tombol). */
  is_complete?: boolean;
}

export function emptyCvData(): CvData {
  return {
    fontFamily: "default",
    fontSize: "default",
    personal: { name: "", email: "", phone: "", address: "" },
    summary: "",
    experiences: [],
    education: [],
    organizations: [],
    skills: { hard: "", soft: "" },
    languages: "",
    certificates: [],
    projects: [],
  };
}

/**
 * Bentuk data legacy (sebelum 2026-09-04): `certificates` dan `projects`
 * dulu disimpan sebagai satu string, bukan array. Dipakai hanya sebagai tipe
 * input di `normalizeCvData` — `CvData` publik tetap mendeklarasikan keduanya
 * sebagai array supaya kode konsumen tidak perlu null-check.
 */
type LegacyCvData = Omit<CvData, "certificates" | "projects"> & {
  certificates?: CvData["certificates"] | string;
  projects?: CvData["projects"] | string;
};

/** Normalisasi data lama — konversi string certificates ke array, dll. */
export function normalizeCvData(input: CvData): CvData {
  const d = input as LegacyCvData;
  if (!d.fontFamily) d.fontFamily = "default";
  if (!d.fontSize) d.fontSize = "default";
  if (typeof d.certificates === "string") {
    const lines = d.certificates
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
    d.certificates = lines.map((name) => ({
      name,
      issuer: "",
      year: "",
      credentialId: "",
    }));
  }
  if (typeof d.projects === "string") {
    const s = d.projects.trim();
    d.projects = s
      ? [{ title: s, role: "—", objective: "", techStack: "" }]
      : [];
  }
  return d as CvData;
}
