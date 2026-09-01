export type CvTemplateId = "modern" | "classic" | "neon";

export interface CvTemplateConfig {
  id: CvTemplateId;
  label: string;
  font: string;
  headerAlign: "left" | "center";
  nameUppercase: boolean;
  headerMargin: string;
  h1Class: string;
  linkClass: string;
  otherMode: "combined" | "split";
  layout: "single" | "mixed";
  accent: string;
  hasBorder: boolean;
  hasQr: boolean;
}

export const CV_TEMPLATES: Record<CvTemplateId, CvTemplateConfig> = {
  modern: {
    id: "modern",
    label: "Modern",
    font: "font-sans",
    headerAlign: "left",
    nameUppercase: false,
    headerMargin: "mb-6",
    h1Class: "text-2xl font-bold tracking-tight text-slate-900",
    linkClass:
      "text-[#1e40af] underline decoration-slate-300 underline-offset-2 hover:decoration-[#1e40af]",
    otherMode: "combined",
    layout: "single",
    accent: "#1e40af",
    hasBorder: false,
    hasQr: false,
  },
  classic: {
    id: "classic",
    label: "Classic",
    font: "font-serif",
    headerAlign: "center",
    nameUppercase: true,
    headerMargin: "mb-3",
    h1Class:
      "text-2xl font-bold uppercase tracking-wide text-slate-900 sm:text-[26px]",
    linkClass:
      "underline decoration-slate-300 underline-offset-2 hover:decoration-slate-900",
    otherMode: "split",
    layout: "single",
    accent: "#0f172a",
    hasBorder: false,
    hasQr: false,
  },
  neon: {
    id: "neon",
    label: "Neon",
    font: "font-sans",
    headerAlign: "left",
    nameUppercase: false,
    headerMargin: "mb-7",
    h1Class: "text-[42px] font-bold tracking-[-0.04em] text-[#111]",
    linkClass: "text-[#444] hover:underline",
    otherMode: "split",
    layout: "single",
    accent: "#6ee7b7",
    hasBorder: false,
    hasQr: false,
  },
};

export function getTemplateConfig(id: string): CvTemplateConfig {
  return (
    (CV_TEMPLATES as Record<string, CvTemplateConfig>)[id] ??
    CV_TEMPLATES.classic
  );
}
