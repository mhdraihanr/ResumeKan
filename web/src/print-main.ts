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

const app = createApp({
  render() {
    return h(CvPreview, { data, template, language, compact: true });
  },
});
app.mount("#print-app");
