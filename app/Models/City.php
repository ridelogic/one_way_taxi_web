<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['name', 'slug', 'state', 'description', 'media_id', 'is_popular_destination', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_popular_destination' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function departingRoutes(): HasMany
    {
        return $this->hasMany(TaxiRoute::class, 'origin_city_id');
    }

    public function arrivingRoutes(): HasMany
    {
        return $this->hasMany(TaxiRoute::class, 'destination_city_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Match free text typed by a customer ("chennai", "Chennai, Tamil Nadu", "Pondicherry") to an active city,
     * by name, slug or an alias from config/cities.php.
     */
    public static function resolve(?string $text): ?self
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        $name = mb_strtolower(trim(explode(',', $text)[0]));
        $aliasSlug = static::slugForAlias($name);

        return static::active()
            ->where(fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', [$name])
                ->orWhereRaw('LOWER(slug) = ?', [$name])
                ->when($aliasSlug, fn (Builder $q) => $q->orWhere('slug', $aliasSlug)))
            ->first();
    }

    /** The city slug an alias belongs to ("pondicherry" → "puducherry"), if any. */
    private static function slugForAlias(string $lowerName): ?string
    {
        foreach ((array) config('cities.aliases', []) as $slug => $aliases) {
            foreach ((array) $aliases as $alias) {
                if (mb_strtolower($alias) === $lowerName) {
                    return (string) $slug;
                }
            }
        }

        return null;
    }

    /** @return list<string> Other names this city is known by (config/cities.php). */
    public function aliases(): array
    {
        return array_values((array) config('cities.aliases.'.$this->slug, []));
    }

    /** Hint shown next to the city in location suggestions: the state, plus any other names. */
    public function suggestionHint(): string
    {
        $aliases = $this->aliases();

        return trim(($this->state ?? '').($aliases ? ' · also '.implode(', ', $aliases) : ''), ' ·');
    }

    public function label(): string
    {
        return $this->state ? $this->name.', '.$this->state : $this->name;
    }
}
