<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'template' => 'required|in:modern,classic,neon',
            'language' => 'required|in:id,en',
            'data' => 'required|array',
            'data.personal' => 'required|array',
            'data.personal.name' => 'required|string|max:100',
            'data.personal.email' => 'required|email',
            'data.personal.phone' => 'required|string|max:30',
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
            'data.skills' => 'nullable|array',
            'data.skills.hard' => 'nullable|string|max:500',
            'data.skills.soft' => 'nullable|string|max:300',
            'data.languages' => 'nullable|string|max:200',
            'data.certificates' => 'nullable|array|max:5',
            'data.certificates.*.name' => 'required_with:data.certificates|string|max:100',
            'data.certificates.*.issuer' => 'required_with:data.certificates|string|max:100',
            'data.certificates.*.year' => 'required_with:data.certificates|string|max:10',
            'data.certificates.*.credentialId' => 'nullable|string|max:100',
            'data.projects' => 'nullable|array|max:5',
            'data.projects.*.title' => 'required_with:data.projects|string|max:100',
            'data.projects.*.role' => 'required_with:data.projects|string|max:100',
            'data.projects.*.objective' => 'nullable|string|max:500',
            'data.projects.*.techStack' => 'nullable|string|max:200',
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
        $this->merge(['data' => $data]);
    }
}
