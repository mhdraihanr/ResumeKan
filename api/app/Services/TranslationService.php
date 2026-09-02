<?php

namespace App\Services;

class TranslationService
{
    /**
     * ponytail: unofficial Google gtx endpoint — no SLA, can break/rate-limit.
     * Upgrade path: Google Cloud Translation API or LibreTranslate proxy.
     */
    public function translate(array $data, string $target = 'en'): array
    {
        $items = $this->collect($data);

        if ($items === []) {
            return $data;
        }

        $translated = $this->translateBatch($items, $target);

        return $this->apply($data, $items, $translated);
    }

    /**
     * Flatten translatable strings (content only — names, companies,
     * institutions, URLs, numbers stay verbatim).
     *
     * @return list<string> translatable strings, in stable order
     */
    private function collect(array $data): array
    {
        $out = [];
        $push = function ($field) use (&$out) {
            if (is_string($field) && trim($field) !== '') {
                $out[] = $field;
            }
        };

        $push($data['summary'] ?? null);
        foreach ($data['experiences'] ?? [] as $e) {
            $push($e['position'] ?? null);
            $push($e['description'] ?? null);
        }
        foreach ($data['education'] ?? [] as $e) {
            $push($e['degree'] ?? null);
            $push($e['achievements'] ?? null);
        }
        foreach ($data['organizations'] ?? [] as $o) {
            $push($o['role'] ?? null);
            $push($o['description'] ?? null);
        }
        if (isset($data['skills']) && is_array($data['skills'])) {
            $push($data['skills']['hard'] ?? null);
            $push($data['skills']['soft'] ?? null);
        }
        $push($data['languages'] ?? null);
        $push($data['certificates'] ?? null);
        foreach ($data['projects'] ?? [] as $p) {
            $push($p['title'] ?? null);
            $push($p['objective'] ?? null);
        }

        return $out;
    }

    /**
     * One gtx request for the whole CV: join fields with a delimiter,
     * translate once, split back. Falls back to per-item requests when
     * Google mangles the delimiter.
     *
     * @param list<string> $items
     * @return list<string> translated, aligned with $items
     */
    private function translateBatch(array $items, string $target): array
    {
        $joined = implode(' @@@ ', $items);
        $parts = array_map('trim', explode('@@@', $this->request($joined, $target)));

        if (count($parts) === count($items)) {
            return $parts;
        }

        return array_map(fn ($s) => $this->request($s, $target), $items);
    }

    /** Raw gtx call; returns concatenated translated segments. */
    private function request(string $text, string $target): string
    {
        if (trim($text) === '') {
            return '';
        }

        // ponytail: file_get_contents instead of Http client — PHP cURL on this
        // Windows dev box fails TLS (no CA bundle); OpenSSL stream works. The
        // stream wrapper handles GET fine here; swap to Http::get() if proxies/
        // retries are ever needed.
        $url = 'https://translate.googleapis.com/translate_a/single?' . http_build_query([
            'client' => 'gtx',
            'sl' => 'id',
            'tl' => $target,
            'dt' => 't',
            'ie' => 'UTF-8',
            'oe' => 'UTF-8',
            'q' => $text,
        ]);

        $res = @file_get_contents($url);

        if ($res === false) {
            throw new \RuntimeException('Translation service unavailable');
        }

        $body = json_decode($res, true);

        if (! is_array($body)) {
            throw new \RuntimeException('Translation service unavailable');
        }

        $out = '';
        foreach ($body[0] ?? [] as $seg) {
            $out .= $seg[0] ?? '';
        }

        return $out;
    }

    /**
     * Write translated strings back into the CvData tree in the same
     * order they were collected.
     */
    private function apply(array $data, array $items, array $translated): array
    {
        $idx = 0;
        $set = function (&$field) use ($translated, &$idx) {
            if (is_string($field) && trim($field) !== '') {
                $field = $translated[$idx++] ?? $field;
            }
        };

        $set($data['summary']);

        foreach ($data['experiences'] ?? [] as $ei => $_) {
            $set($data['experiences'][$ei]['position']);
            $set($data['experiences'][$ei]['description']);
        }

        foreach ($data['education'] ?? [] as $di => $_) {
            $set($data['education'][$di]['degree']);
            $set($data['education'][$di]['achievements']);
        }

        foreach ($data['organizations'] ?? [] as $oi => $_) {
            $set($data['organizations'][$oi]['role']);
            $set($data['organizations'][$oi]['description']);
        }

        if (isset($data['skills']) && is_array($data['skills'])) {
            $set($data['skills']['hard']);
            $set($data['skills']['soft']);
        }
        $set($data['languages']);
        $set($data['certificates']);

        foreach ($data['projects'] ?? [] as $pi => $_) {
            $set($data['projects'][$pi]['title']);
            $set($data['projects'][$pi]['objective']);
        }

        return $data;
    }
}
