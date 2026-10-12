<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Validasi unggahan import CV. Batasnya selaras dengan backlog §7a: PDF/DOCX,
 * maks 5 MB. Jumlah halaman dan keterbacaan isi diperiksa di
 * `DocumentTextExtractor`, bukan di sini.
 */
class ParseCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `File::types()` mencocokkan MIME asli isi berkas (bukan sekadar
            // ekstensi), jadi berkas yang di-rename tetap tertolak. `max` dalam
            // kilobyte.
            'file' => ['required', 'file', File::types(['pdf', 'docx'])->max(5 * 1024)],
            'language' => 'nullable|in:id,en',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih berkas CV dulu.',
            'file.file' => 'Berkas tidak valid.',
            // Dipicu juga saat PHP menolak berkas karena `upload_max_filesize`
            // (UPLOAD_ERR_INI_SIZE) — di server, batas itu bisa lebih kecil dari
            // 5 MB yang dijanjikan. Jangan sebut angka di sini supaya pesannya
            // tidak bohong; `file.max` di bawah yang mengurus batas 5 MB.
            'file.uploaded' => 'Berkas gagal diunggah. Kemungkinan ukurannya melebihi batas server.',
            'file.mimes' => 'Format tidak didukung. Upload berkas PDF atau DOCX.',
            'file.mimetypes' => 'Format tidak didukung. Upload berkas PDF atau DOCX.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
            'language.in' => 'Bahasa tidak didukung.',
        ];
    }
}
