<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Draft (`?draft=1`) menyimpan progres setengah jadi, jadi field wajib
     * tidak dipaksakan — hanya bentuk/tipe yang dicek. Submit final memakai
     * ruleset ketat seperti semula.
     */
    public function isDraft(): bool
    {
        return filter_var($this->query('draft', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function rules(): array
    {
        $rules = $this->strictRules();

        if (! $this->isDraft()) {
            return $rules;
        }

        // Draft: `required`/`required_with` → `nullable`. Field tetap harus
        // bertipe benar bila ada isinya, tapi boleh kosong.
        //
        // Rule bisa berupa string (`a|b`) atau array (`['a', 'regex:...']`) —
        // keduanya dinormalkan ke bentuk pipa agar penggantian seragam.
        return array_map(static function (string|array $rule): string {
            $parts = is_array($rule) ? $rule : explode('|', $rule);

            $parts = array_filter(
                $parts,
                static fn (string $p): bool => $p !== 'required'
                    && ! str_starts_with($p, 'required_')
                    && $p !== 'nullable',
            );

            return implode('|', array_merge(['nullable'], $parts));
        }, $rules);
    }

    /**
     * Pesan bahasa Indonesia. Placeholder `:attribute` diisi `attributes()`
     * supaya pengguna melihat "Telepon" alih-alih "data.personal.phone".
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul CV wajib diisi.',
            'data.personal.name.required' => 'Nama wajib diisi.',
            'data.personal.email.required' => 'Email wajib diisi.',
            'data.personal.email.email' => 'Email belum valid — contoh: nama@email.com',
            'data.personal.phone.required' => 'Telepon wajib diisi.',
            'data.personal.phone.regex' => 'Telepon hanya boleh angka dan simbol + - ( ) . serta minimal 7 digit.',
            'data.personal.phone.max' => 'Telepon maksimal 30 karakter.',
            'data.personal.address.required' => 'Alamat wajib diisi.',

            // Entri berulang: tanpa pesan khusus, Laravel memakai teks Inggris
            // default ("The data.certificates.0.issuer field is required when
            // data.certificates is present"). Pesan ini menggantinya dengan
            // kalimat Indonesia yang menyebut field-nya, dan `attributes()`
            // mengisi `:attribute` dengan label ramah.
            'data.experiences.*.company.required_with' => ':attribute wajib diisi.',
            'data.experiences.*.position.required_with' => ':attribute wajib diisi.',
            'data.experiences.*.startDate.required_with' => ':attribute wajib diisi.',
            'data.experiences.*.endDate.required_with' => ':attribute wajib diisi.',
            'data.education.*.institution.required_with' => ':attribute wajib diisi.',
            'data.education.*.degree.required_with' => ':attribute wajib diisi.',
            'data.education.*.year.required_with' => ':attribute wajib diisi.',
            'data.organizations.*.organization.required_with' => ':attribute wajib diisi.',
            'data.organizations.*.role.required_with' => ':attribute wajib diisi.',
            'data.organizations.*.period.required_with' => ':attribute wajib diisi.',
            'data.skills.*.label.required_with' => ':attribute wajib diisi.',
            'data.certificates.*.name.required_with' => ':attribute wajib diisi.',
            'data.certificates.*.issuer.required_with' => ':attribute wajib diisi.',
            'data.certificates.*.year.required_with' => ':attribute wajib diisi.',
            'data.projects.*.title.required_with' => ':attribute wajib diisi.',
            'data.projects.*.role.required_with' => ':attribute wajib diisi.',

            // Batas jumlah per section (pesan bawaan juga berbahasa Inggris).
            'data.experiences.max' => 'Maksimal :max pengalaman.',
            'data.education.max' => 'Maksimal :max pendidikan.',
            'data.organizations.max' => 'Maksimal :max organisasi.',
            'data.skills.max' => 'Maksimal :max grup keahlian.',
            'data.certificates.max' => 'Maksimal :max sertifikat.',
            'data.projects.max' => 'Maksimal :max proyek.',
        ];
    }

    /**
     * Label ramah untuk field wajib. Tanpa ini, pesan default Laravel memakai
     * path mentah (mis. "data.personal.phone" atau "data.certificates.0.issuer").
     */
    public function attributes(): array
    {
        $attributes = [
            'title' => 'Judul CV',
            'data.personal.name' => 'Nama',
            'data.personal.email' => 'Email',
            'data.personal.phone' => 'Telepon',
            'data.personal.address' => 'Alamat',
        ];

        // Label per field entri, plus nomor entri supaya pengguna tahu baris
        // mana yang perlu diperbaiki ("Penerbit (Sertifikat #2) wajib diisi.").
        $entryLabels = [
            'experiences' => [
                'section' => 'Pengalaman',
                'fields' => [
                    'company' => 'Perusahaan',
                    'position' => 'Posisi',
                    'startDate' => 'Tanggal mulai',
                    'endDate' => 'Tanggal selesai',
                ],
            ],
            'education' => [
                'section' => 'Pendidikan',
                'fields' => [
                    'institution' => 'Institusi',
                    'degree' => 'Gelar & jurusan',
                    'year' => 'Tahun',
                ],
            ],
            'organizations' => [
                'section' => 'Organisasi',
                'fields' => [
                    'organization' => 'Organisasi',
                    'role' => 'Peran',
                    'period' => 'Periode',
                ],
            ],
            'skills' => [
                'section' => 'Keahlian',
                'fields' => ['label' => 'Nama grup keahlian'],
            ],
            'certificates' => [
                'section' => 'Sertifikat',
                'fields' => [
                    'name' => 'Nama sertifikat',
                    'issuer' => 'Penerbit',
                    'year' => 'Tahun terbit',
                ],
            ],
            'projects' => [
                'section' => 'Proyek',
                'fields' => [
                    'title' => 'Nama proyek',
                    'role' => 'Peran',
                ],
            ],
        ];

        foreach ($entryLabels as $key => $meta) {
            $count = is_array($this->input("data.$key")) ? count($this->input("data.$key")) : 0;

            for ($i = 0; $i < $count; $i++) {
                foreach ($meta['fields'] as $field => $label) {
                    $attributes["data.$key.$i.$field"] = "$label ({$meta['section']} #".($i + 1).')';
                }
            }
        }

        return $attributes;
    }

    protected function strictRules(): array
    {
        return [
            'title' => 'required|string|max:100',            'template' => 'required|in:modern,classic,neon',
            'language' => 'required|in:id,en',
            'data' => 'required|array',
            'data.fontFamily' => 'nullable|string|max:50',
            'data.fontSize' => 'nullable|string|max:50',
            'data.personal' => 'required|array',
            'data.personal.name' => 'required|string|max:100',
            'data.personal.email' => 'required|email',
            'data.personal.phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+().\-\s]{7,30}$/'],
            'data.personal.address' => 'required|string|max:200',
            'data.personal.linkedin' => 'nullable|string|max:500',
            'data.personal.website' => 'nullable|string|max:500',
            'data.personal.github' => 'nullable|string|max:500',
            'data.personal.photo' => 'nullable|string|max:2000',
            'data.summary' => 'nullable|string|max:600',
            'data.experiences' => 'nullable|array|max:10',
            'data.experiences.*.company' => 'required_with:data.experiences|string',
            'data.experiences.*.position' => 'required_with:data.experiences|string',
            'data.experiences.*.location' => 'nullable|string',
            'data.experiences.*.employmentType' => 'nullable|string|in:Full-time,Part-time,Internship,Contract,Freelance',
            'data.experiences.*.startDate' => 'required_with:data.experiences|string',
            'data.experiences.*.endDate' => 'required_with:data.experiences|string',
            'data.experiences.*.description' => 'nullable|string|max:1500',
            'data.education' => 'nullable|array|max:5',
            'data.education.*.institution' => 'required_with:data.education|string',
            'data.education.*.degree' => 'required_with:data.education|string',
            'data.education.*.location' => 'nullable|string',
            'data.education.*.year' => 'required_with:data.education|string',
            'data.education.*.gpa' => 'nullable|string|max:10',
            'data.education.*.achievements' => 'nullable|string|max:1000',
            'data.organizations' => 'nullable|array|max:5',
            'data.organizations.*.organization' => 'required_with:data.organizations|string|max:100',
            'data.organizations.*.role' => 'required_with:data.organizations|string|max:100',
            'data.organizations.*.period' => 'required_with:data.organizations|string|max:30',
            'data.organizations.*.description' => 'nullable|string|max:800',
            'data.skills' => 'nullable|array|max:5',
            'data.skills.*.label' => 'required_with:data.skills|string|max:40',
            'data.skills.*.items' => 'nullable|string|max:500',
            'data.languages' => 'nullable|string|max:200',
            'data.certificates' => 'nullable|array|max:5',
            'data.certificates.*.name' => 'required_with:data.certificates|string|max:100',
            'data.certificates.*.issuer' => 'required_with:data.certificates|string|max:100',
            'data.certificates.*.year' => 'required_with:data.certificates|string|max:10',
            'data.certificates.*.credentialId' => 'nullable|string|max:100',
            'data.projects' => 'nullable|array|max:8',
            'data.projects.*.title' => 'required_with:data.projects|string|max:100',
            'data.projects.*.role' => 'required_with:data.projects|string|max:100',
            'data.projects.*.objective' => 'nullable|string|max:500',
            'data.projects.*.techStack' => 'nullable|string|max:200',
            'data.projects.*.link' => 'nullable|string|max:500',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('data') && is_string($this->input('data'))) {
            $decoded = json_decode($this->input('data'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['data' => $decoded]);
            }
        }

        $data = $this->input('data');
        if (!is_array($data)) return;

        // Normalisasi linkedin/website/github: dukung www. tanpa scheme
        foreach (['linkedin', 'website', 'github'] as $k) {
            $v = $data['personal'][$k] ?? null;
            if (is_string($v) && trim($v) !== '') {
                $t = trim($v);
                if (!preg_match('#^https?://#i', $t)) {
                    $t = 'https://' . ltrim($t, '/');
                }
                $data['personal'][$k] = $t;
            }
        }

        // Backward compat: certificates string lama → array 1 item
        if (isset($data['certificates']) && is_string($data['certificates'])) {
            $str = trim($data['certificates']);
            if ($str === '') {
                $data['certificates'] = [];
            } else {
                $data['certificates'] = [['name' => $str, 'issuer' => '—', 'year' => '', 'credentialId' => '']];
            }
        }

        // Backward compat: projects string lama → array 1 item (role placeholder agar lolos required_with)
        if (isset($data['projects']) && is_string($data['projects'])) {
            $str = trim($data['projects']);
            if ($str === '') {
                $data['projects'] = [];
            } else {
                $data['projects'] = [['title' => $str, 'role' => '—', 'objective' => '', 'techStack' => '']];
            }
        }
        // Normalisasi link proyek: dukung www. tanpa scheme
        foreach ($data['projects'] ?? [] as $i => $pr) {
            $v = $pr['link'] ?? null;
            if (is_string($v) && trim($v) !== '') {
                $t = trim($v);
                if (!preg_match('#^https?://#i', $t)) {
                    $t = 'https://' . ltrim($t, '/');
                }
                $data['projects'][$i]['link'] = $t;
            }
        }

        // Backward compat: skills object lama `{hard, soft}` -> array grup.
        // Grup default `Hard skills`/`Soft skills` selalu di dua posisi pertama;
        // grup yang items-nya kosong dibuang supaya tidak jadi section hampa.
        if (isset($data['skills']) && ! array_is_list($data['skills'])) {
            $old = $data['skills'];
            $groups = [
                ['label' => 'Hard skills', 'items' => $old['hard'] ?? ''],
                ['label' => 'Soft skills', 'items' => $old['soft'] ?? ''],
            ];
            $data['skills'] = array_values(array_filter(
                $groups,
                static fn (array $g): bool => trim((string) $g['items']) !== '',
            ));
        }
        $this->merge(['data' => $data]);
    }
}
