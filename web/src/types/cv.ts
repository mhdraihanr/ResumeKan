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
  skills?: CvSkill[];
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

/**
 * Satu grup keahlian. `label` bebas diisi user (mis. "Library & Frameworks"),
 * kecuali dua grup bawaan `Hard skills` / `Soft skills` yang selalu ada.
 */
export interface CvSkill {
  label: string;
  items: string;
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
    skills: defaultSkillGroups(),
    languages: "",
    certificates: [],
    projects: [],
  };
}

/** Label grup skill bawaan — dipakai form & migrasi data lama. */
export const SKILL_HARD_LABEL = "Hard skills";
export const SKILL_SOFT_LABEL = "Soft skills";

/** Dua grup skill bawaan (selalu ada, di posisi 2 teratas). */
export function defaultSkillGroups(): CvSkill[] {
  return [
    { label: SKILL_HARD_LABEL, items: "" },
    { label: SKILL_SOFT_LABEL, items: "" },
  ];
}

/**
 * Bentuk data legacy (sebelum 2026-09-04): `certificates` dan `projects`
 * dulu disimpan sebagai satu string, bukan array. Dipakai hanya sebagai tipe
 * input di `normalizeCvData` — `CvData` publik tetap mendeklarasikan keduanya
 * sebagai array supaya kode konsumen tidak perlu null-check.
 */
type LegacyCvData = Omit<CvData, "certificates" | "projects" | "skills"> & {
  certificates?: CvData["certificates"] | string;
  projects?: CvData["projects"] | string;
  /** Sebelum 2026-09-18: `skills` = `{ hard, soft }`. */
  skills?: CvSkill[] | { hard?: string; soft?: string };
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
  d.skills = normalizeSkills(d.skills);
  return d as CvData;
}

/**
 * `skills` lama (`{ hard, soft }`) -> array grup, dengan `Hard skills` /
 * `Soft skills` selalu di dua posisi pertama. Grup duplikat dari migrasi
 * dibuang, lalu grup custom user dipertahankan apa adanya. Dua grup bawaan
 * selalu ada (walau kosong) supaya form selalu punya keduanya untuk diisi.
 *
 * @param input nilai `skills` mentah dari server (array, object lama, atau kosong)
 */
export function normalizeSkills(input: unknown): CvSkill[] {
  const raw: CvSkill[] = [];

  if (Array.isArray(input)) {
    for (const g of input) {
      if (!g || typeof g !== "object") continue;
      const label = typeof g.label === "string" ? g.label.trim() : "";
      const items = typeof g.items === "string" ? g.items : "";
      raw.push({ label, items });
    }
  } else if (input && typeof input === "object") {
    const old = input as { hard?: unknown; soft?: unknown };
    if (typeof old.hard === "string" && old.hard.trim() !== "")
      raw.push({ label: SKILL_HARD_LABEL, items: old.hard });
    if (typeof old.soft === "string" && old.soft.trim() !== "")
      raw.push({ label: SKILL_SOFT_LABEL, items: old.soft });
  }

  const hard = raw.find((g) => g.label === SKILL_HARD_LABEL) ?? {
    label: SKILL_HARD_LABEL,
    items: "",
  };
  const soft = raw.find((g) => g.label === SKILL_SOFT_LABEL) ?? {
    label: SKILL_SOFT_LABEL,
    items: "",
  };
  const custom = raw.filter(
    (g) => g.label !== SKILL_HARD_LABEL && g.label !== SKILL_SOFT_LABEL,
  );

  return [hard, soft, ...custom];
}

/** Grup yang punya item — dipakai preview/PDF agar section kosong tidak render. */
export function filledSkillGroups(skills?: CvSkill[]): CvSkill[] {
  return (skills ?? []).filter((g) => g.items.trim() !== "");
}
