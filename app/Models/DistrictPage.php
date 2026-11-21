<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A location landing page. Tamil Nadu districts live at /tamil-nadu/{slug}; a Union Territory (state other than
 * Tamil Nadu, e.g. Puducherry) is its own top-level page at /{slug}. Content is managed in Admin → District pages;
 * routes and fares come from the existing route data of the cities linked to the page.
 */
class DistrictPage extends Model
{
    public const TAMIL_NADU = 'Tamil Nadu';

    /** Union Territory pages with their own top-level URL (/{slug}); each must have a page whose state is not Tamil Nadu. */
    public const TERRITORY_SLUGS = ['puducherry'];

    protected $fillable = [
        'name', 'slug', 'state', 'headquarters', 'region', 'heading', 'intro', 'summary', 'content', 'pickup_locations', 'popular_destinations',
        'nearest_airport', 'related_districts', 'hero_media_id', 'seo_title', 'seo_description', 'is_published', 'sort_order',
        'drop_locations', 'popular_routes', 'faqs', 'service_texts', 'card_media_id', 'image_alt', 'seo_keywords', 'canonical_url',
        'og_title', 'og_description', 'og_media_id', 'content_reviewed', 'hidden_services',
    ];

    protected function casts(): array
    {
        return [
            'pickup_locations' => 'array',
            'popular_destinations' => 'array',
            'related_districts' => 'array',
            'drop_locations' => 'array',
            'popular_routes' => 'array',
            'faqs' => 'array',
            'service_texts' => 'array',
            'hidden_services' => 'array',
            'content_reviewed' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function heroMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'hero_media_id');
    }

    /** Card image on the homepage / directory grid; falls back to the hero image. */
    public function cardMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'card_media_id');
    }

    public function ogMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_media_id');
    }

    /** YouTube promotions assigned to this district (Admin → Video Promotions). */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(PromoVideo::class);
    }

    public function cardImage(): ?MediaAsset
    {
        return $this->cardMedia ?? $this->heroMedia;
    }

    public function imageAlt(): string
    {
        return $this->image_alt ?: 'Taxi service in '.$this->name.', '.($this->state ?: self::TAMIL_NADU);
    }

    /** A Tamil Nadu district (the default), as opposed to a separate Union Territory such as Puducherry. */
    public function isTamilNadu(): bool
    {
        return ($this->state ?: self::TAMIL_NADU) === self::TAMIL_NADU;
    }

    /** "District" or "Union Territory", for labels next to the name. */
    public function typeLabel(): string
    {
        return $this->isTamilNadu() ? 'District' : 'Union Territory';
    }

    /** Public URL: /tamil-nadu/{slug} for districts, /{slug} for a Union Territory. */
    public function url(): string
    {
        return ! $this->isTamilNadu() && in_array($this->slug, self::TERRITORY_SLUGS, true)
            ? route('territories.show', $this->slug)
            : route('districts.show', $this->slug);
    }

    /** @return list<string> */
    public function dropLocations(): array
    {
        return $this->cleanList($this->drop_locations);
    }

    /** Cars offered in this district (Admin → District Management → Cars). */
    public function districtVehicles(): HasMany
    {
        return $this->hasMany(DistrictVehicle::class);
    }

    /** Popular routes managed per district (Admin → District Management → Routes). */
    public function districtRoutes(): HasMany
    {
        return $this->hasMany(DistrictRoute::class);
    }

    public function districtFaqs(): HasMany
    {
        return $this->hasMany(DistrictFaq::class);
    }

    public function galleryPhotos(): BelongsToMany
    {
        return $this->belongsToMany(GalleryPhoto::class);
    }

    /** Active district routes, in display order. @return list<array{from: string, to: string, description: ?string, route: ?DistrictRoute}> */
    public function curatedRoutes(): array
    {
        return $this->districtRoutes()->active()->ordered()->with('taxiRoute.origin', 'taxiRoute.destination')->get()
            ->map(fn (DistrictRoute $r) => ['from' => $r->from_location, 'to' => $r->to_location, 'description' => $r->description, 'route' => $r])
            ->all();
    }

    /** Active FAQs, in display order. @return list<array{question: string, answer: string}> */
    public function faqItems(): array
    {
        return $this->districtFaqs()->active()->ordered()->get(['question', 'answer'])
            ->map(fn (DistrictFaq $f) => ['question' => $f->question, 'answer' => $f->answer])
            ->all();
    }

    /** Trip-type keys whose service card is switched off for this district. */
    public function serviceVisible(string $key): bool
    {
        return ! in_array($key, (array) $this->hidden_services, true);
    }

    /** Editor's description for one trip type (one_way, round_trip, local, rental, airport), or null for the default. */
    public function serviceText(string $key): ?string
    {
        $text = trim((string) (((array) $this->service_texts)[$key] ?? ''));

        return $text !== '' ? $text : null;
    }

    public function canonicalUrl(): string
    {
        return $this->canonical_url ?: $this->url();
    }

    /** @param  mixed  $value */
    private function cleanList($value): array
    {
        return array_values(array_filter((array) $value, fn ($v) => is_string($v) && trim($v) !== ''));
    }

    /** Existing booking cities inside this district (e.g. Dindigul → Kodaikanal); their routes and fares appear on the page. */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Only the Tamil Nadu districts (the /tamil-nadu directory). */
    public function scopeTamilNadu(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('state', self::TAMIL_NADU)->orWhereNull('state')->orWhere('state', ''));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function displayHeading(): string
    {
        return $this->heading ?: 'Reliable Taxi Services in '.$this->name;
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: 'Taxi Service in '.$this->name;
    }

    public function seoDescription(): string
    {
        return $this->seo_description ?: 'Book reliable one-way, round-trip and local taxi services in '.$this->name.' with One Way Taxi. Explore routes and book your trip.';
    }

    /** The place name used to prefill the booking form: a linked booking city when there is one, else the headquarters. */
    public function pickupName(): string
    {
        return $this->cities->first()?->name ?? ($this->headquarters ?: $this->name);
    }

    /** @return list<string> */
    public function pickupLocations(): array
    {
        return array_values(array_filter((array) $this->pickup_locations, fn ($v) => is_string($v) && trim($v) !== ''));
    }

    /** @return list<string> */
    public function popularDestinations(): array
    {
        return array_values(array_filter((array) $this->popular_destinations, fn ($v) => is_string($v) && trim($v) !== ''));
    }

    /** Short card description: the summary, else the first sentence of the introduction. */
    public function cardSummary(): string
    {
        return (string) ($this->summary ?: str($this->intro)->before('. ')->limit(110));
    }

    /** Published related districts, in the order the editor listed them. */
    public function relatedPages(): Collection
    {
        $slugs = array_values(array_filter((array) $this->related_districts, 'is_string'));
        if ($slugs === []) {
            return new Collection;
        }

        return static::published()->whereIn('slug', $slugs)->whereKeyNot($this->id)->get()
            ->sortBy(fn (self $page) => array_search($page->slug, $slugs, true))->values();
    }

    /** Active routes that start or end in one of the district's cities, popular first. */
    public function routes(int $limit = 6): Collection
    {
        $cityIds = $this->cities->pluck('id')->all();
        if ($cityIds === []) {
            return new Collection;
        }

        return TaxiRoute::active()
            ->with(['origin', 'destination'])
            ->where(fn (Builder $q) => $q->whereIn('origin_city_id', $cityIds)->orWhereIn('destination_city_id', $cityIds))
            ->orderByRaw('CASE WHEN origin_city_id IN ('.implode(',', array_map('intval', $cityIds)).') THEN 0 ELSE 1 END')
            ->orderByDesc('is_popular')->ordered()
            ->limit($limit)->get();
    }
}
