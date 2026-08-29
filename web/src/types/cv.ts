export interface CvData {
  personal: {
    name: string;
    email: string;
    phone: string;
    address: string;
    linkedin?: string;
    website?: string;
    github?: string;
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
  certificates?: string;
  projects?: {
    title: string;
    role: string;
    objective?: string;
    techStack?: string;
  }[];
}

export interface Cv {
  id: number;
  title: string;
  template: "modern" | "classic";
  language: "id" | "en";
  data?: CvData;
  updated_at: string;
  created_at?: string;
}

export function emptyCvData(): CvData {
  return {
    personal: { name: "", email: "", phone: "", address: "" },
    summary: "",
    experiences: [],
    education: [],
    organizations: [],
    skills: { hard: "", soft: "" },
    languages: "",
    certificates: "",
    projects: [],
  };
}
