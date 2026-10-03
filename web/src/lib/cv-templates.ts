export type CvTemplateId = "modern" | "classic" | "neon";

export interface CvTemplateConfig {
  id: CvTemplateId;
  label: string;
  atsFriendly: boolean;
  /**
   * Inline font-family untuk teks CV. Selalu font Google (bukan font sistem)
   * agar PDF di Windows dan container Linux merender glyph yang sama — font
   * sistem berbeda antar-OS (Georgia vs LiberationSerif) sehingga PDF server
   * tidak konsisten dengan hasil lokal.
   */
  font: string;
  /** Nama family Google Fonts yang dimuat untuk `font` bawaan template. */
  googleFamily: string;
  /** Nama kandidat ditampilkan uppercase (classic) atau apa adanya. */
  nameUppercase: boolean;
}

export const CV_TEMPLATES: Record<CvTemplateId, CvTemplateConfig> = {
  modern: {
    id: "modern",
    label: "Modern",
    atsFriendly: true,
    font: "'Inter', sans-serif",
    googleFamily: "Inter",
    nameUppercase: false,
  },
  classic: {
    id: "classic",
    label: "Classic",
    atsFriendly: true,
    font: "'Lora', serif",
    googleFamily: "Lora",
    nameUppercase: true,
  },
  neon: {
    id: "neon",
    label: "Neon",
    atsFriendly: true,
    font: "'Inter', sans-serif",
    googleFamily: "Inter",
    nameUppercase: false,
  },
};

export function getTemplateConfig(id: string): CvTemplateConfig {
  return (
    (CV_TEMPLATES as Record<string, CvTemplateConfig>)[id] ??
    CV_TEMPLATES.classic
  );
}

export interface CvFontOption {
  id: string;
  label: string;
  /** Tailwind class or inline font-family value */
  family: string;
  /** Google Fonts family name for <link> loading. Null = system font, no load needed. */
  googleFamily: string | null;
}

export const CV_FONTS: CvFontOption[] = [
  {
    id: "default",
    label: "Bawaan Template",
    family: "",
    googleFamily: null,
  },
  {
    id: "inter",
    label: "Inter (Modern Sans)",
    family: "'Inter', sans-serif",
    googleFamily: "Inter",
  },
  {
    id: "source-sans",
    label: "Source Sans 3 (Corporate)",
    family: "'Source Sans 3', sans-serif",
    googleFamily: "Source+Sans+3",
  },
  {
    id: "source-serif",
    label: "Source Serif 4 (Formal Serif)",
    family: "'Source Serif 4', serif",
    googleFamily: "Source+Serif+4",
  },
  {
    id: "lora",
    label: "Lora (Artistic Serif)",
    family: "'Lora', serif",
    googleFamily: "Lora",
  },
  {
    id: "merriweather",
    label: "Merriweather (Classic Editorial)",
    family: "'Merriweather', serif",
    googleFamily: "Merriweather",
  },
];

export type CvFontSizeId = "compact" | "default" | "spacious";

export interface CvFontSizeOption {
  id: CvFontSizeId;
  label: string;
  bodyPt: number;
}

export const CV_FONT_SIZES: CvFontSizeOption[] = [
  { id: "compact", label: "Kompak (10pt base)", bodyPt: 10 },
  { id: "default", label: "Standar (11pt base)", bodyPt: 11 },
  { id: "spacious", label: "Lega (12pt base)", bodyPt: 12 },
];
