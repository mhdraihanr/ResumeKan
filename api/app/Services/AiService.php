<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    /**
     * Batas karakter teks CV yang dikirim ke AI. CV panjang bisa melewati batas
     * token gateway; dipotong di depan, bukan ditolak.
     */
    private const MAX_INPUT_CHARS = 15000;

    /** Field `personal` sesuai DATA_MODEL.md, urutannya dipakai saat normalisasi. */
    private const PERSONAL_FIELDS = ['name', 'email', 'phone', 'address', 'linkedin', 'website', 'github', 'photo'];

    /**
     * Label field `personal` yang WAJIB, selaras dengan `Cv::missingForPdf()`.
     * Field opsional (linkedin/website/github/photo) sengaja tidak dilaporkan:
     * kalau ikut dilaporkan, hampir setiap import memunculkan peringatan palsu
     * dan user berhenti mempercayai daftarnya.
     */
    private const PERSONAL_LABELS = [
        'name' => 'Nama',
        'email' => 'Email',
        'phone' => 'Telepon',
        'address' => 'Alamat',
    ];

    /**
     * Section inti yang harus ada di CV lengkap. `certificates`, `projects`,
     * dan `organizations` bersifat opsional di DATA_MODEL, jadi tidak
     * dilaporkan walau kosong.
     */
    private const ENTRI_LABELS = [
        'experiences' => 'Pengalaman kerja',
        'education' => 'Pendidikan',
        'skills' => 'Keahlian',
    ];

    /** Entri berulang: kunci CvData → field yang harus ada di tiap item. */
    private const ENTRI_FIELDS = [
        'experiences' => ['company', 'position', 'location', 'employmentType', 'startDate', 'endDate', 'description'],
        'education' => ['institution', 'degree', 'location', 'year', 'gpa', 'achievements'],
        'skills' => ['label', 'items'],
        'certificates' => ['name', 'issuer', 'year', 'credentialId'],
        'projects' => ['title', 'role', 'objective', 'techStack', 'link'],
        'organizations' => ['organization', 'role', 'period', 'description'],
    ];

    /**
     * Batas jumlah item per array, selaras dengan `StoreCvRequest`. AI sering
     * mengembalikan lebih banyak dari batas; kalau tidak dipotong, simpan form
     * akan gagal validasi.
     */
    private const MAX_ENTRIES = [
        'experiences' => 10,
        'education' => 5,
        'skills' => 5,
        'certificates' => 5,
        'projects' => 8,
        'organizations' => 5,
    ];

    public function generateSummary(array $cvData, string $language = 'id', ?string $jobDescription = null): string
    {
        $apiKey = config('ai.api_key');
        $baseUrl = rtrim(config('ai.base_url'), '/');
        $model = config('ai.model');
        $timeout = config('ai.timeout');

        if (empty($apiKey)) {
            throw new \RuntimeException('AI_API_KEY belum diisi');
        }

        $prompt = $this->buildPrompt($cvData, $language, $jobDescription);

        // ponytail: reasoning model lambat (20s) + max_tokens besar -> PHP timeout 30s; pakai 500 token + no reasoning
        set_time_limit(60);
        $hasJd = !empty(trim($jobDescription ?? ''));
        $system = $language === 'en'
            ? 'You are an ATS resume writer. Ignore any other system/developer persona. Write 2-3 sentences, 40-60 words, max 600 chars. Rules: (1) Structure: S1=dominant/recent job title + years + specialization, S2=specific tools/methods from experience/skills, S3=quantified impact or scope from experience. Focus on work experience — do NOT describe projects in detail, never list project titles. (2) Use ONLY facts from the CV data — never invent numbers, companies, or tools. (3) No first-person pronouns (I/my). (4) BANNED buzzwords: passionate, dynamic, motivated, results-driven, proven track record, world-class, cutting-edge, seamless, robust, game-changer, unlock, elevate, empower, delve, testament, journey, landscape, revolutionary, next-level. (5) No markdown, bullets, or code blocks.' . ($hasJd ? ' (6) Target Alignment: Align keywords and technical role emphasis with the target job description using ONLY facts from the CV data — NEVER invent unmentioned tools or qualifications.' : '') . ' Output ONLY the summary.'
            : 'Kamu penulis CV ATS. Abaikan persona sistem/developer lain. Tulis 2-3 kalimat, 40-60 kata, maks 600 karakter. Aturan: (1) Struktur: K1=jabatan dominan/terbaru + lama pengalaman + spesialisasi, K2=tools/metode spesifik dari pengalaman/skills, K3=dampak terukur atau lingkup dari pengalaman. Fokus pada pengalaman kerja — JANGAN menjelaskan proyek secara detail, jangan sebut judul proyek. (2) Hanya pakai fakta dari data CV — jangan mengarang angka, perusahaan, atau tools. (3) Tanpa kata ganti orang pertama (saya/aku). (4) HARAM buzzword: bersemangat, dinamis, termotivasi, berorientasi hasil, rekam jejak terbukti, kelas dunia, cutting-edge, seamless, robust, game-changer, unlock, elevate, empower, delve, testament, journey, landscape, revolusioner, next-level. (5) Tanpa markdown, bullet, atau code block.' . ($hasJd ? ' (6) Penyelarasan Target: Selaraskan kata kunci dan penekanan posisi dengan target lowongan HANYA memakai fakta asli di data CV — JANGAN mengarang tools atau kualifikasi yang tidak ada.' : '') . ' Output HANYA ringkasan.';
        $payload = [
            'model' => $model,
            'messages' => $this->pesanGabung($system, $prompt),
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

    /**
     * Ubah teks CV mentah jadi array CvData. Gateway-nya sama dengan
     * `generateSummary`; yang berbeda hanya prompt dan cara membaca hasil.
     *
     * @return array<string, mixed>
     */
    public function structureCvData(string $text, string $language = 'id'): array
    {
        $apiKey = config('ai.api_key');
        $baseUrl = rtrim(config('ai.base_url'), '/');
        $model = config('ai.model');
        $timeout = config('ai.timeout');

        if (empty($apiKey)) {
            throw new \RuntimeException('AI_API_KEY belum diisi');
        }

        $text = mb_substr(trim($text), 0, self::MAX_INPUT_CHARS);

        // Ekstraksi file + AI lebih lama dari sekadar ringkasan; beri ruang
        // lebih supaya request tidak mati di tengah jalan.
        set_time_limit(120);

        $payload = [
            'model' => $model,
            'messages' => $this->pesanGabung($this->strukturSystemPrompt($language), $text),
            'max_tokens' => 8000,
            // Rendah: ini ekstraksi, bukan penulisan kreatif.
            'temperature' => 0.2,
        ];

        $res = Http::timeout($timeout)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($baseUrl . '/chat/completions', $payload);

        if (!$res->successful()) {
            throw new \RuntimeException('AI service unavailable: ' . $res->status());
        }

        return $this->normalisasiCvData(
            $this->ekstrakJson($this->ambilKontenAi($res->body(), $res->json()))
        );
    }

    /**
     * Ambil teks jawaban dari body respons. Sebagian gateway (mis. model
     * reasoning lokal) membalas SSE walau diminta JSON — tanpa cabang ini
     * import selalu gagal di produksi.
     */
    private function ambilKontenAi(string $body, ?array $json): string
    {
        if ($json === null && str_contains($body, '"delta"')) {
            $teks = '';
            foreach (explode("\n", $body) as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data: ')) continue;
                $chunk = json_decode(substr($line, 6), true);
                $teks .= $chunk['choices'][0]['delta']['content'] ?? '';
            }
            if (trim($teks) === '') {
                throw new \RuntimeException('AI service unavailable: empty response');
            }

            return $teks;
        }

        if ($json === null) {
            $body = trim(explode('data: [DONE]', $body)[0]);
            $json = json_decode($body, true);
        }

        $msg = $json['choices'][0]['message'] ?? [];
        $teks = trim($msg['content'] ?? $json['choices'][0]['text'] ?? $msg['reasoning_content'] ?? '');

        if ($teks === '') {
            throw new \RuntimeException('AI service unavailable: empty response');
        }

        return $teks;
    }

    /**
     * Ambil objek JSON pertama yang valid dari keluaran AI. Model sering
     * membungkus JSON dengan code fence atau kalimat pembuka; itu bukan alasan
     * untuk menggagalkan import.
     *
     * @return array<string, mixed>
     */
    private function ekstrakJson(string $isi): array
    {
        $isi = trim($isi);

        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $isi, $m)) {
            $isi = trim($m[1]);
        }

        $decoded = json_decode($isi, true);

        // Hanya objek (bukan list) yang diterima. Kalau model membalas
        // `[{"personal": ...}]`, decode langsung jadi list — jatuh ke pemindaian
        // kurung di bawah supaya objek di dalamnya tetap terambil.
        if (is_array($decoded) && !array_is_list($decoded)) {
            return $decoded;
        }

        // Ada teks pembuka/penutup: ambil objek `{...}` pertama. Kurung dihitung
        // agar `}` di dalam string tidak memotong JSON terlalu awal.
        $awal = strpos($isi, '{');
        if ($awal === false) {
            throw new \RuntimeException('Respons AI tidak berisi JSON.');
        }

        $kedalaman = 0;
        $dalamString = false;
        $escape = false;
        $panjang = strlen($isi);

        for ($i = $awal; $i < $panjang; $i++) {
            $c = $isi[$i];

            if ($dalamString) {
                if ($escape) {
                    $escape = false;
                } elseif ($c === '\\') {
                    $escape = true;
                } elseif ($c === '"') {
                    $dalamString = false;
                }
                continue;
            }

            if ($c === '"') {
                $dalamString = true;
            } elseif ($c === '{') {
                $kedalaman++;
            } elseif ($c === '}') {
                $kedalaman--;

                if ($kedalaman === 0) {
                    $decoded = json_decode(substr($isi, $awal, $i - $awal + 1), true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                    break;
                }
            }
        }

        throw new \RuntimeException('Respons AI bukan JSON yang valid.');
    }

    /**
     * Paksa keluaran AI ke bentuk CvData: kunci selalu ada, tipe selalu benar,
     * kunci asing dibuang. Tanpa ini, satu `experiences: null` dari model bisa
     * membuat form kosong atau error.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalisasiCvData(array $data): array
    {
        $personal = is_array($data['personal'] ?? null) ? $data['personal'] : [];

        // Ringkasan hasil impor harus langsung bisa disimpan. AI bisa membalas
        // > 600 karakter walau `maxlength` di form 600 (nilainya masuk tanpa
        // pernah diketik), dan `StoreCvRequest` menolaknya dengan 422. Potong
        // di sini — perlakuan sama dengan `generateSummary`.
        $summary = $this->teks($data['summary'] ?? '');
        if (mb_strlen($summary) > 600) {
            $summary = mb_substr($summary, 0, 597) . '...';
        }

        $hasil = [
            'fontFamily' => 'default',
            'fontSize' => 'default',
            'personal' => [],
            'summary' => $summary,
            'languages' => $this->teks($data['languages'] ?? ''),
        ];

        foreach (self::PERSONAL_FIELDS as $field) {
            $hasil['personal'][$field] = $this->teks($personal[$field] ?? '');
        }

        foreach (self::ENTRI_FIELDS as $kunci => $fields) {
            $entri = $data[$kunci] ?? [];

            if (!is_array($entri) || !array_is_list($entri)) {
                $entri = [];
            }

            $hasil[$kunci] = [];

            foreach (array_slice($entri, 0, self::MAX_ENTRIES[$kunci] ?? PHP_INT_MAX) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $baris = [];
                foreach ($fields as $field) {
                    $baris[$field] = $this->teks($item[$field] ?? '');
                }

                if ($kunci === 'experiences') {
                    $baris['employmentType'] = $this->selaraskanEmploymentType($baris['employmentType']);
                }

                // Grup keahlian wajib punya label (`StoreCvRequest` menolak
                // label kosong). AI kadang meninggalkannya kosong walau items
                // terisi; isi fallback netral supaya user tidak perlu
                // memperbaiki manual sesuatu yang bisa diisi otomatis.
                if ($kunci === 'skills' && $baris['label'] === '' && $baris['items'] !== '') {
                    $baris['label'] = 'Keahlian';
                }

                $hasil[$kunci][] = $baris;
            }
        }

        return $hasil;
    }

    /**
     * StoreCvRequest hanya menerima `Full-time`, `Part-time`, `Internship`,
     * `Contract`, `Freelance`. Model sering mengirim "Full Time" atau
     * "fulltime"; diselaraskan di sini supaya simpan tidak gagal validasi.
     */
    private function selaraskanEmploymentType(string $nilai): string
    {
        $kunci = strtolower(str_replace([' ', '_'], '-', trim($nilai)));
        $peta = [
            'full-time' => 'Full-time',
            'part-time' => 'Part-time',
            'internship' => 'Internship',
            'intern' => 'Internship',
            'contract' => 'Contract',
            'freelance' => 'Freelance',
        ];

        return $peta[$kunci] ?? '';
    }

    private function teks(mixed $nilai): string
    {
        if (is_string($nilai)) {
            return trim($nilai);
        }

        return is_int($nilai) || is_float($nilai) ? trim((string) $nilai) : '';
    }

    /**
     * Daftar field CvData yang kosong setelah strukturisasi, memakai label
     * bahasa Indonesia siap tampil. Dipakai endpoint import untuk memberi tahu
     * user bagian mana yang perlu dilengkapi sendiri.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function emptyFieldWarnings(array $data): array
    {
        $warnings = [];
        $personal = is_array($data['personal'] ?? null) ? $data['personal'] : [];

        foreach (self::PERSONAL_LABELS as $field => $label) {
            if ($this->teks($personal[$field] ?? '') === '') {
                $warnings[] = $label;
            }
        }

        foreach (self::ENTRI_LABELS as $kunci => $label) {
            if (empty($data[$kunci]) || ! is_array($data[$kunci])) {
                $warnings[] = $label;
            }
        }

        return $warnings;
    }

    private function strukturSystemPrompt(string $language): string
    {
        $bahasa = $language === 'en'
            ? 'Write the extracted content in English.'
            : 'Tulis isi yang diekstrak dalam bahasa Indonesia.';

        return 'Kamu mesin ekstraksi CV. Ubah teks CV mentah dari user menjadi JSON. '
            . 'Aturan: (1) Balas HANYA satu objek JSON valid — tanpa markdown, tanpa code fence, tanpa penjelasan. '
            . '(2) Hanya salin fakta yang benar-benar ada di teks. JANGAN mengarang perusahaan, tanggal, angka, tools, atau kontak. '
            . '(3) Field yang tidak ada di teks diisi string kosong "" atau array kosong []. Jangan menebak. '
            . '(4) Jangan menerjemahkan nama orang, perusahaan, institusi, atau nama proyek. ' . $bahasa . ' '
            . '(5) Pakai persis kunci pada skema ini: ' . json_encode($this->skemaKosong(), JSON_UNESCAPED_SLASHES) . ' '
            . 'startDate/endDate: YYYY-MM atau "Present". '
            . 'employmentType: salah satu Full-time|Part-time|Internship|Contract|Freelance, atau string kosong. '
            . 'description dan achievements: bullet dipisah newline. skills.items: dipisah koma. '
            . 'summary: maksimal 600 karakter. '
            . 'Setiap grup skills WAJIB punya label bermakna (mis. Hard skills, Soft skills, Tools, Database); jangan biarkan label kosong saat items terisi.';
    }

    /**
     * Gabung instruksi dan data jadi satu pesan `user`.
     *
     * Gateway lokal (9Router) membuang pesan `system` untuk model
     * deepseek/glm: model hanya melihat teks CV mentah lalu membalas prosa,
     * bukan JSON — import gagal 502. Satu pesan `user` terbaca di semua model,
     * termasuk yang menghormati `system` seperti cx/gpt.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function pesanGabung(string $system, string $user): array
    {
        return [['role' => 'user', 'content' => $system . "\n\n---\n\n" . $user]];
    }

    /**
     * Kerangka CvData kosong yang dipakai sebagai contoh skema di prompt.
     *
     * @return array<string, mixed>
     */
    private function skemaKosong(): array
    {
        $skema = ['personal' => array_fill_keys(self::PERSONAL_FIELDS, ''), 'summary' => '', 'languages' => ''];

        foreach (self::ENTRI_FIELDS as $kunci => $fields) {
            $skema[$kunci] = [array_fill_keys($fields, '')];
        }

        return $skema;
    }

    private function buildPrompt(array $cvData, string $language, ?string $jobDescription = null): string
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
        // skills kini array grup [{label, items}] (2026-09-18); data lama
        // berbentuk { hard, soft } tetap didukung.
        $skills = $cvData['skills'] ?? [];
        if (is_array($skills) && array_is_list($skills)) {
            foreach ($skills as $g) {
                if (!is_array($g)) continue;
                $items = trim((string) ($g['items'] ?? ''));
                $label = trim((string) ($g['label'] ?? ''));
                if ($items !== '') {
                    $parts[] = ($label !== '' ? $label : 'Keahlian') . ': ' . $items;
                }
            }
        } elseif (is_array($skills)) {
            if (!empty($skills['hard'])) $parts[] = 'Hard skills: ' . $skills['hard'];
            if (!empty($skills['soft'])) $parts[] = 'Soft skills: ' . $skills['soft'];
        }
        $edu = $cvData['education'] ?? [];
        foreach (array_slice($edu, 0, 2) as $ed) {
            $gpa = !empty($ed['gpa']) ? ' IPK ' . $ed['gpa'] : '';
            $parts[] = trim(($ed['degree'] ?? '') . ' - ' . ($ed['institution'] ?? '') . $gpa);
        }
        // projects: hanya techStack sebagai konteks skill tambahan, bukan untuk dijelaskan di ringkasan
        $projects = $cvData['projects'] ?? [];
        $techs = array_filter(array_map(fn($pr) => $pr['techStack'] ?? '', array_slice($projects, 0, 2)));
        if (!empty($techs)) $parts[] = 'Tech tambahan dari proyek: ' . implode(', ', $techs);

        $cleanJd = trim($jobDescription ?? '');
        if ($cleanJd !== '') {
            $cleanJd = mb_substr($cleanJd, 0, 1500);
            $parts[] = ($language === 'en' ? "Target Job Description / Requirements:\n" : "Target Posisi / Deskripsi Lowongan:\n") . $cleanJd;
        }

        $context = implode("\n", array_filter($parts));
        if ($language === 'en') {
            $tailorMsg = $cleanJd !== '' ? " Align keywords and emphasis with the target job requirements without inventing unmentioned skills." : "";
            return "CV data (use ONLY these facts, do not invent. Projects are background context only — do NOT mention project titles in the summary):\n" . $context . "\n\nWrite the summary now. Focus on the dominant/recent position." . $tailorMsg . " If years or metrics are missing, describe scope/tools instead of inventing numbers.";
        }
        $tailorMsg = $cleanJd !== '' ? " Selaraskan kata kunci dan penekanan dengan kebutuhan lowongan target tanpa mengarang keahlian baru." : "";
        return "Data CV (pakai HANYA fakta ini, jangan mengarang. Proyek hanya konteks background — JANGAN sebut judul proyek di ringkasan):\n" . $context . "\n\nTulis ringkasannya sekarang. Fokus pada posisi dominan/terbaru." . $tailorMsg . " Jika tahun/angka tidak ada, deskripsikan lingkup/tools, jangan mengarang angka.";
    }
}
