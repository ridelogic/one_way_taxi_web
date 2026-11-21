<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

/**
 * A promotional announcement in the offers ticker below the header (Admin → Promotional Announcements).
 * Shown only while enabled AND inside its optional start/end window AND on a page it targets; disabled by default.
 * Targets: the homepage (show_on_home) and/or location pages (location_scope: none | all | selected + pivot).
 */
class PromoAnnouncement extends Model
{
    public const SCOPE_NONE = 'none';

    public const SCOPE_ALL = 'all';

    public const SCOPE_SELECTED = 'selected';

    /** Admin "Display locations" choices → [show_on_home, location_scope]. */
    public const TARGETS = [
        'home' => ['Homepage only', true, self::SCOPE_NONE],
        'all_locations' => ['All location pages', false, self::SCOPE_ALL],
        'selected_locations' => ['Selected location pages', false, self::SCOPE_SELECTED],
        'home_all_locations' => ['Homepage + all location pages', true, self::SCOPE_ALL],
        'home_selected_locations' => ['Homepage + selected location pages', true, self::SCOPE_SELECTED],
    ];

    protected $fillable = ['title', 'message', 'link_url', 'starts_at', 'ends_at', 'show_on_home', 'location_scope', 'sort_order', 'is_enabled'];

    protected $attributes = ['is_enabled' => false, 'sort_order' => 0, 'show_on_home' => true, 'location_scope' => self::SCOPE_NONE];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
            'is_enabled' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    /** Location pages chosen for "selected location pages". */
    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(DistrictPage::class);
    }

    /** The table exists (its migration has been run). Until then nothing is shown and the admin page explains why. */
    public static function tableReady(): bool
    {
        return static::schemaState() !== 'missing';
    }

    /** Page targeting (homepage / location pages) is available: its migration (…_000004) has been run. */
    public static function targetingReady(): bool
    {
        return static::schemaState() === 'ready';
    }

    /**
     * 'missing' (no table yet), 'legacy' (table without page targeting: homepage-only, as before) or 'ready'.
     * Checked once per request; migrations cannot run mid-request.
     */
    public static function schemaState(): string
    {
        return static::$schemaState ??= match (true) {
            ! Schema::hasTable('promo_announcements') => 'missing',
            ! Schema::hasColumn('promo_announcements', 'location_scope') || ! Schema::hasTable('district_page_promo_announcement') => 'legacy',
            default => 'ready',
        };
    }

    private static ?string $schemaState = null;

    /** Re-check the schema on the next call (after running migrations in the same process, e.g. in tests). */
    public static function forgetSchemaState(): void
    {
        static::$schemaState = null;
    }

    /**
     * Announcements for one page's ticker, in display order — one query: enabled, inside the window and targeting
     * that page (the homepage when $district is null, else that location page). Empty if none, or no table yet.
     * Before the targeting migration has run, the homepage keeps its previous behaviour and location pages show none.
     */
    public static function forTicker(?DistrictPage $district = null): Collection
    {
        if (! static::tableReady()) {
            return new Collection;
        }
        if (! static::targetingReady()) {
            return $district === null ? static::live()->ordered()->get() : new Collection;
        }

        return static::live()->ordered()
            ->when($district === null, fn (Builder $q) => $q->where('show_on_home', true))
            ->when($district !== null, fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('location_scope', self::SCOPE_ALL)
                ->orWhere(fn (Builder $q) => $q->where('location_scope', self::SCOPE_SELECTED)
                    ->whereHas('districts', fn (Builder $d) => $d->whereKey($district->getKey())))))
            ->get();
    }

    /** The admin "Display locations" choice this announcement matches (see TARGETS). */
    public function target(): string
    {
        foreach (self::TARGETS as $key => [, $home, $scope]) {
            if ($this->show_on_home === $home && $this->location_scope === $scope) {
                return $key;
            }
        }

        return 'home';
    }

    /** Short summary for the admin list, e.g. "Homepage + 3 location pages". */
    public function targetSummary(): string
    {
        if (! static::targetingReady()) {
            return 'Homepage only';
        }

        $locations = match ($this->location_scope) {
            self::SCOPE_ALL => 'all location pages',
            self::SCOPE_SELECTED => ($n = $this->districts->count()).' selected location '.($n === 1 ? 'page' : 'pages'),
            default => null,
        };

        return match (true) {
            $this->show_on_home && $locations !== null => 'Homepage + '.$locations,
            $this->show_on_home => 'Homepage only',
            $locations !== null => ucfirst($locations),
            default => 'Nowhere',
        };
    }

    /** Enabled and currently inside the display window — what the public ticker shows. */
    public function scopeLive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_enabled', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isLive(): bool
    {
        $now = now();

        return $this->is_enabled
            && ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gt($now));
    }

    /** Admin status: Disabled, Scheduled (enabled, not started), Expired (enabled, ended) or Live. */
    public function displayStatus(): string
    {
        return match (true) {
            ! $this->is_enabled => 'Disabled',
            $this->ends_at !== null && $this->ends_at->lte(now()) => 'Expired',
            $this->starts_at !== null && $this->starts_at->gt(now()) => 'Scheduled',
            default => 'Live',
        };
    }

    /** True for links that leave the website (opened in a new tab). */
    public function isExternalLink(): bool
    {
        return $this->link_url !== null && preg_match('#^https?://#i', $this->link_url) === 1
            && parse_url($this->link_url, PHP_URL_HOST) !== parse_url(url('/'), PHP_URL_HOST);
    }

    /** Absolute URL for the link: site paths ("/book") are resolved against the website. */
    public function linkHref(): ?string
    {
        if ($this->link_url === null || $this->link_url === '') {
            return null;
        }

        return str_starts_with($this->link_url, '/') ? url($this->link_url) : $this->link_url;
    }
}
