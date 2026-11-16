<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Cached key/value store for global site settings with schema defaults from config/settings.php.
 */
class Settings
{
    private const CACHE_KEY = 'site_settings.all';

    /** @var array<string, string|null>|null */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
            return $values[$key];
        }

        return $default ?? $this->schemaDefault($key);
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key, '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /** Multi-line setting split into trimmed, non-empty lines. @return array<int, string> */
    public function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->get($key, '')))));
    }

    public function media(string $key): ?MediaAsset
    {
        $id = $this->get($key);

        return $id ? MediaAsset::find($id) : null;
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            return $this->values = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('site_settings')) {
                    return [];
                }

                return SiteSetting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            return $this->values = [];
        }
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values): void
    {
        $groups = $this->keyGroups();

        foreach ($values as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value, 'group' => $groups[$key] ?? 'general'],
            );
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function schemaDefault(string $key): mixed
    {
        foreach (config('settings', []) as $group) {
            if (isset($group['fields'][$key])) {
                return $group['fields'][$key]['default'] ?? null;
            }
        }

        return null;
    }

    /** @return array<string, string> key => group */
    private function keyGroups(): array
    {
        $map = [];
        foreach (config('settings', []) as $group => $definition) {
            foreach (array_keys($definition['fields']) as $key) {
                $map[$key] = $group;
            }
        }

        return $map;
    }

    /** Phone number reduced to characters valid in a tel: link. */
    public function telLink(?string $phone = null): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', $phone ?? (string) $this->get('phone'));
    }
}
