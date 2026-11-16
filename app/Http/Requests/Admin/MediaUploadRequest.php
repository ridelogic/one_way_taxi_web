<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaUploader;

class MediaUploadRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $image = ['bail', 'file', 'mimes:'.implode(',', MediaUploader::ALLOWED_EXTENSIONS), 'max:'.MediaUploader::MAX_KILOBYTES, 'dimensions:max_width=8000,max_height=8000'];

        return [
            'files' => ['required_without:file', 'array', 'max:20'],
            'files.*' => $image,
            'file' => array_merge(['required_without:files'], $image),
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    protected function fieldNames(): array
    {
        return ['files.*' => 'image', 'file' => 'image'];
    }

    public function messages(): array
    {
        return [
            'files.*.mimes' => 'Only JPG, PNG, WebP, GIF or ICO images can be uploaded.',
            'file.mimes' => 'Only JPG, PNG, WebP, GIF or ICO images can be uploaded.',
            'files.*.max' => 'Each image must be 5 MB or smaller.',
            'file.max' => 'Images must be 5 MB or smaller.',
        ];
    }
}
