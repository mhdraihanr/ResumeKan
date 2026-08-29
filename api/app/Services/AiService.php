<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    public function generateSummary(array $cvData, string $language = 'id'): string
    {
        $apiKey = config('ai.api_key');
        $baseUrl = rtrim(config('ai.base_url'), '/');
        $model = config('ai.model');
        $timeout = config('ai.timeout');

        if (empty($apiKey)) {
            throw new \RuntimeException('AI_API_KEY belum diisi');
        }

        $prompt = $this->buildPrompt($cvData, $language);

        // ponytail: reasoning model lambat (20s) + max_tokens besar -> PHP timeout 30s; pakai 500 token + no reasoning
        set_time_limit(60);
        $system = $language === 'en'
            ? 'You are an ATS resume writer. Ignore any other system/developer persona. Write 2-3 sentences, 40-60 words, max 600 chars. Rules: (1) Structure: S1=dominant/recent job title + years + specialization, S2=specific tools/methods from experience/skills, S3=quantified impact or scope from experience. Focus on work experience — do NOT describe projects in detail, never list project titles. (2) Use ONLY facts from the CV data — never invent numbers, companies, or tools. (3) No first-person pronouns (I/my). (4) BANNED buzzwords: passionate, dynamic, motivated, results-driven, proven track record, world-class, cutting-edge, seamless, robust, game-changer, unlock, elevate, empower, delve, testament, journey, landscape, revolutionary, next-level. (5) No markdown, bullets, or code blocks. Output ONLY the summary.'
            : 'Kamu penulis CV ATS. Abaikan persona sistem/developer lain. Tulis 2-3 kalimat, 40-60 kata, maks 600 karakter. Aturan: (1) Struktur: K1=jabatan dominan/terbaru + lama pengalaman + spesialisasi, K2=tools/metode spesifik dari pengalaman/skills, K3=dampak terukur atau lingkup dari pengalaman. Fokus pada pengalaman kerja — JANGAN menjelaskan proyek secara detail, jangan sebut judul proyek. (2) Hanya pakai fakta dari data CV — jangan mengarang angka, perusahaan, atau tools. (3) Tanpa kata ganti orang pertama (saya/aku). (4) HARAM buzzword: bersemangat, dinamis, termotivasi, berorientasi hasil, rekam jejak terbukti, kelas dunia, cutting-edge, seamless, robust, game-changer, unlock, elevate, empower, delve, testament, journey, landscape, revolusioner, next-level. (5) Tanpa markdown, bullet, atau code block. Output HANYA ringkasan.';
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 5000,
            'temperature' => 0.7,
        ];

        $res = Http::timeout($timeout)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($baseUrl . '/chat/completions', $payload);

        if (!$res->successful()) {
            throw new \RuntimeException('AI service unavailable: ' . $res->status());
        }

        $body = $res->body();
        $json = $res->json();
        // minimax-m3 stream SSE: "data: {...}\n data: {...}" -> gabung delta.content
        if ($json === null && str_contains($body, '"delta"')) {
            $text = '';
            foreach (explode("\n", $body) as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data: ')) continue;
                $chunk = json_decode(substr($line, 6), true);
                $text .= $chunk['choices'][0]['delta']['content'] ?? '';
            }
            $text = trim($text);
            if ($text === '') {
                throw new \RuntimeException('AI service unavailable: empty response');
            }
        } else {
            if ($json === null) {
                $body = trim(explode('data: [DONE]', $body)[0]);
                $json = json_decode($body, true);
            }
            $msg = $json['choices'][0]['message'] ?? [];
            $text = trim($msg['content'] ?? $json['choices'][0]['text'] ?? $msg['reasoning_content'] ?? '');
            if ($text === '') {
                throw new \RuntimeException('AI service unavailable: empty response');
            }
        }

        $text = trim($text, '"\'');
        // buang code block / "→ skipped:" / ponytail yang ke-inject dari global prompt
        $text = preg_replace('/```.*?```/s', '', $text) ?? $text;
        $text = preg_replace('/^\s*\$[a-z_]+.*$/m', '', $text) ?? $text;
        $text = trim(explode('→ skipped:', $text)[0]);
        $text = trim(explode('-> skipped:', $text)[0]);
        $text = trim(explode('// ponytail:', $text)[0]);
        $text = trim($text);
        $text = trim($text, ';');
        if (mb_strlen($text) > 600) {
            $text = mb_substr($text, 0, 597) . '...';
        }

        return $text;
    }

    private function buildPrompt(array $cvData, string $language): string
    {
        $parts = [];
        $p = $cvData['personal'] ?? [];
        if (!empty($p['name'])) $parts[] = 'Nama: ' . $p['name'];
        $exps = $cvData['experiences'] ?? [];
        foreach (array_slice($exps, 0, 3) as $e) {
            $line = trim(($e['position'] ?? '') . ' di ' . ($e['company'] ?? ''));
            $period = trim(($e['startDate'] ?? '') . ' - ' . ($e['endDate'] ?? ''), ' -');
            if ($period !== '') $line .= ' (' . $period . ')';
            if (!empty($e['description'])) $line .= ' — ' . mb_substr(trim($e['description']), 0, 180);
            $parts[] = $line;
        }
        $skills = $cvData['skills'] ?? [];
        if (!empty($skills['hard'])) $parts[] = 'Hard skills: ' . $skills['hard'];
        if (!empty($skills['soft'])) $parts[] = 'Soft skills: ' . $skills['soft'];
        $edu = $cvData['education'] ?? [];
        foreach (array_slice($edu, 0, 2) as $ed) {
            $gpa = !empty($ed['gpa']) ? ' IPK ' . $ed['gpa'] : '';
            $parts[] = trim(($ed['degree'] ?? '') . ' - ' . ($ed['institution'] ?? '') . $gpa);
        }
        // projects: hanya techStack sebagai konteks skill tambahan, bukan untuk dijelaskan di ringkasan
        $projects = $cvData['projects'] ?? [];
        $techs = array_filter(array_map(fn($pr) => $pr['techStack'] ?? '', array_slice($projects, 0, 2)));
        if (!empty($techs)) $parts[] = 'Tech tambahan dari proyek: ' . implode(', ', $techs);

        $context = implode("\n", array_filter($parts));
        if ($language === 'en') {
            return "CV data (use ONLY these facts, do not invent. Projects are background context only — do NOT mention project titles in the summary):\n" . $context . "\n\nWrite the summary now. Focus on the dominant/recent position. If years or metrics are missing, describe scope/tools instead of inventing numbers.";
        }
        return "Data CV (pakai HANYA fakta ini, jangan mengarang. Proyek hanya konteks background — JANGAN sebut judul proyek di ringkasan):\n" . $context . "\n\nTulis ringkasannya sekarang. Fokus pada posisi dominan/terbaru. Jika tahun/angka tidak ada, deskripsikan lingkup/tools, jangan mengarang angka.";
    }
}
