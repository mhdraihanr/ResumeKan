import "./assets/main.css";
import { createApp, h } from "vue";
import CvPreview from "./components/cv/CvPreview.vue";
import type { CvData } from "./types/cv";
import { normalizeCvData } from "./types/cv";

declare global {
  interface Window {
    __CV_DATA__?: CvData;
    __CV_TEMPLATE__?: string;
    __CV_LANGUAGE__?: string;
  }
}

const data: CvData = normalizeCvData(
  (window.__CV_DATA__ as CvData | undefined) ?? {
    personal: { name: "", email: "", phone: "", address: "" },
    summary: "",
    experiences: [],
    education: [],
    organizations: [],
    skills: { hard: "", soft: "" },
    languages: "",
    certificates: [],
    projects: [],
  },
);
const template: string = window.__CV_TEMPLATE__ ?? "modern";
const language: string = window.__CV_LANGUAGE__ ?? "id";

async function bootstrap() {
  const app = createApp({
    render() {
      return h(CvPreview, {
        data,
        template,
        language,
        compact: true,
        fontFamily: data.fontFamily ?? "default",
        fontSize: data.fontSize ?? "default",
      });
    },
  });
  app.mount("#print-app");

  // Tunggu Google Fonts ter-load penuh sebelum Puppeteer mengambil snapshot PDF
  if (typeof document !== "undefined" && document.fonts) {
    try {
      await document.fonts.ready;
    } catch {
      // ignore
    }
  }
}

bootstrap();
