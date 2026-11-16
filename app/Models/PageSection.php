<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class PageSection extends Model
{
    protected $fillable = ['page_id', 'key', 'type', 'title', 'subtitle', 'content', 'media_id', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    /** Read a value from the JSON content column using dot notation. */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->content ?? [], $key, $default);
    }

    /** Repeater items stored under content.items. */
    public function items(): array
    {
        return array_values(array_filter($this->get('items', []), fn ($item) => is_array($item)));
    }

    /** Schema definition from config/sections.php for this section type. */
    public function schema(): array
    {
        return config('sections.'.$this->type, []);
    }
}
