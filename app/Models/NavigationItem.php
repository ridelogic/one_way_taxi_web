<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NavigationItem extends Model
{
    public const MENUS = [
        'header' => 'Header navigation',
        'footer_company' => 'Footer — Company',
        'footer_services' => 'Footer — Services',
        'footer_legal' => 'Footer — Legal',
    ];

    protected $fillable = ['menu', 'label', 'url', 'open_in_new_tab', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Relative URLs ("/routes") are resolved against the app base so the site works in a sub-folder. */
    public function href(): string
    {
        if (Str::startsWith($this->url, ['http://', 'https://', 'mailto:', 'tel:', '#'])) {
            return $this->url;
        }

        return url($this->url);
    }

    public function isCurrent(): bool
    {
        $path = trim(parse_url($this->href(), PHP_URL_PATH) ?? '', '/');
        $current = trim(request()->getPathInfo(), '/');
        $base = trim(parse_url(url('/'), PHP_URL_PATH) ?? '', '/');
        $path = trim(Str::after($path, $base), '/');

        return $path === '' ? $current === '' : ($current === $path || Str::startsWith($current, $path.'/'));
    }
}
