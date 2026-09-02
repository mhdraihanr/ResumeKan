// Label dokumen CV per bahasa (PRD F7). Konten user tidak disentuh.
const LABELS = {
  id: {
    experience: "Pengalaman Kerja",
    education: "Pendidikan",
    organizations: "Organisasi",
    skills: "Keahlian",
    projects: "Proyek",
    certificates: "Sertifikasi",
    languages: "Bahasa",
    other: "Lainnya",
    hardSkills: "Hard skills:",
    softSkills: "Soft skills:",
    gpa: "IPK:",
    role: "Peran:",
    techStack: "Tech Stack:",
    position: "Posisi",
    company: "Perusahaan",
    organization: "Organisasi",
  },
  en: {
    experience: "Work Experience",
    education: "Education",
    organizations: "Organizations",
    skills: "Skills",
    projects: "Projects",
    certificates: "Certificates",
    languages: "Languages",
    other: "Other",
    hardSkills: "Hard skills:",
    softSkills: "Soft skills:",
    gpa: "GPA:",
    role: "Role:",
    techStack: "Tech Stack:",
    position: "Position",
    company: "Company",
    organization: "Organization",
  },
} as const;

export type CvLanguage = "id" | "en";
export type CvLabels = (typeof LABELS)[CvLanguage];

export function getLabels(language?: string): CvLabels {
  return LABELS[language === "en" ? "en" : "id"];
}
