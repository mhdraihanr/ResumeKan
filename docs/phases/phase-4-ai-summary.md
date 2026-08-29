# Fase 4 — AI Summary ✅

> Status: **Selesai** (2026-08-28) · Estimasi: ½ hari · Prasyarat: [Fase 3](phase-3-preview-template.md) ✅

## Yang Dikerjakan

- [x] `AiService` (OpenAI-compatible gateway) + `AiController@summary` + `POST /api/v1/ai/summary` + throttle 5/menit
- [x] Tombol "Generate AI" di Ringkasan (hanya saat edit, `cvId` ada) — hasil isi `local.summary`, bisa diedit manual
- [x] Config `config/ai.php` + `.env.example` `AI_API_KEY/BASE_URL/MODEL/TIMEOUT`

## Hasil Implementasi

| File                                        | Isi                                                                                                                                                                                                                                                                                                                     |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `api/config/ai.php`                         | `api_key`, `base_url`, `model`, `timeout` dari `.env`                                                                                                                                                                                                                                                                   |
| `api/app/Services/AiService.php`            | `generateSummary(cvData, language)` → `POST {base_url}/v1/chat/completions` OpenAI-compatible, prompt anti-slop ID/EN (K1=jabatan dominan+lama+spesialisasi / K2=tools / K3=dampak, fokus pengalaman dominan, proyek hanya techStack background, banned buzzwords, hanya fakta CV), max 600 char, SSE streaming support |
| `api/app/Http/Controllers/AiController.php` | Validasi `cv_id`, cek owner `403`, panggil `AiService`, `502`/`503` on error                                                                                                                                                                                                                                            |
| `api/routes/api.php`                        | `POST /ai/summary` + `throttle:5,1`                                                                                                                                                                                                                                                                                     |
| `web/src/api/cv.ts`                         | `cvApi.aiSummary(cvId)`                                                                                                                                                                                                                                                                                                 |
| `web/src/components/cv/CvForm.vue`          | Props `cvId?`, `Generate AI` button + `aiLoading`/`aiError`, `generateSummary()`                                                                                                                                                                                                                                        |
| `web/src/views/CvFormView.vue`              | Pass `:cv-id` ke `CvForm`                                                                                                                                                                                                                                                                                               |

## Referensi

- Kontrak: [API Spec — AI](../API_SPEC.md#ai)
- ADR-5: `Http::post()` ke `AI_BASE_URL/v1/chat/completions`, tanpa SDK — ganti model via `AI_MODEL` di `.env`.
- Prompt anti-slop (antislop-copywriting + Exa ATS): 2-3 kalimat 40-60 kata, K1=jabatan dominan/terbaru+lama+spesialisasi / K2=tools spesifik / K3=dampak terukur; fokus pengalaman kerja dominan — proyek hanya `techStack` sebagai konteks background (jangan sebut judul proyek); banned buzzwords; hanya fakta `cvData`; tanpa `saya/aku`/`I/my`; `buildPrompt` sertakan `description` pengalaman (180 char) + `gpa` + tech proyek agregat.
- `.env`: `AI_API_KEY`, `AI_BASE_URL`, `AI_MODEL` — isi sesuai gateway kamu

## Hasil Verifikasi

| Uji                                               | Hasil   |
| ------------------------------------------------- | ------- |
| `vue-tsc --build`                                 | 0 error |
| `pnpm build`                                      | ok      |
| Tanpa `AI_API_KEY` → `503 AI belum dikonfigurasi` | ✅      |
| Gateway down → `502 AI service unavailable`       | ✅      |
| Throttle ke-6/menit → `429`                       | ✅      |

## Definisi Selesai

- [x] Klik Generate → ringkasan relevan muncul ≤ 5 detik, bisa diedit manual.
- [x] Request ke-6 dalam 1 menit → `429`.
- [x] AI gateway down → `502 { "message": "AI service unavailable" }`.

← [Fase 3](phase-3-preview-template.md) · Lanjut ke [Fase 5](phase-5-pdf.md)
