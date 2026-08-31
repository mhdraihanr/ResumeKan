export type CvTemplateId = "modern" | "classic";

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
  },
};

export function getTemplateConfig(id: string): CvTemplateConfig {
  return (
    (CV_TEMPLATES as Record<string, CvTemplateConfig>)[id] ??
    CV_TEMPLATES.classic
  );
}
