import { computed, toValue } from "vue";
import type { CvData } from "@/types/cv";
import { getTemplateConfig } from "@/lib/cv-templates";

export function useCvData(
  dataRef: CvData | (() => CvData),
  templateRef: string | (() => string),
) {
  const data = () =>
    typeof dataRef === "function" ? (dataRef as () => CvData)() : dataRef;
  const tpl = computed(() =>
    getTemplateConfig(
      typeof templateRef === "function"
        ? (templateRef as () => string)()
        : templateRef,
    ),
  );

  const bullets = (s?: string) =>
    (s ?? "")
      .split("\n")
      .map((x) => x.trim())
      .filter(Boolean);

  function displayUrl(u?: string) {
    if (!u) return "";
    return u.replace(/^https?:\/\//i, "").replace(/\/$/, "");
  }
  function hrefUrl(u?: string) {
    if (!u) return "";
    const t = u.trim();
    if (/^https?:\/\//i.test(t)) return t;
    return "https://" + t.replace(/^\/+/, "");
  }

  const contactDirect = computed(() => {
    const p = data().personal;
    return [p.email, p.phone, p.address].filter(Boolean) as string[];
  });
  const contactLinks = computed(() => {
    const p = data().personal;
    return [
      { label: displayUrl(p.linkedin), href: hrefUrl(p.linkedin) },
      { label: displayUrl(p.website), href: hrefUrl(p.website) },
      { label: displayUrl(p.github), href: hrefUrl(p.github) },
    ].filter((x) => x.label) as { label: string; href: string }[];
  });
  const hasAnyContact = computed(
    () => contactDirect.value.length > 0 || contactLinks.value.length > 0,
  );

  const hardList = computed(() =>
    (data().skills?.hard ?? "")
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean),
  );
  const softList = computed(() =>
    (data().skills?.soft ?? "")
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean),
  );

  function parseDate(s?: string): number {
    if (!s) return 0;
    const t = s.trim().toLowerCase();
    if (t === "sekarang" || t === "present" || t === "current") return 99999999;
    const m = t.match(/(\d{4})[^\d]?(\d{1,2})?/);
    if (m) return parseInt(m[1]!) * 100 + parseInt(m[2] || "0");
    return 0;
  }
  const sortedExperiences = computed(() => {
    const arr = [...(data().experiences ?? [])];
    return arr
      .map((e, idx) => ({ e, idx }))
      .sort((a, b) => {
        const ea = parseDate(a.e.endDate) - parseDate(b.e.endDate);
        if (ea !== 0) return -ea;
        const sa = parseDate(a.e.startDate) - parseDate(b.e.startDate);
        if (sa !== 0) return -sa;
        return b.idx - a.idx;
      })
      .map((x) => x.e);
  });

  const displayName = computed(() =>
    tpl.value.nameUppercase
      ? (data().personal.name || "Nama Anda").toUpperCase()
      : data().personal.name || "Nama Anda",
  );

  return {
    tpl,
    bullets,
    displayUrl,
    hrefUrl,
    contactDirect,
    contactLinks,
    hasAnyContact,
    hardList,
    softList,
    sortedExperiences,
    displayName,
  };
}
