<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use Illuminate\Validation\Rule;

class MediaUpdateRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'credit' => ['nullable', 'string', 'max:500'],
            'aspect_ratio' => ['required', Rule::in(array_keys(MediaAsset::ASPECT_RATIOS))],
            'object_fit' => ['required', Rule::in(array_keys(MediaAsset::OBJECT_FITS))],
            'focal_x' => ['required', 'integer', 'between:0,100'],
            'focal_y' => ['required', 'integer', 'between:0,100'],
        ];
    }
}
