<?php

namespace App\Support;

use App\Models\MediaAsset;

/**
 * Per-page SEO metadata consumed by the public layout.
 */
class Seo
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $image = null,
        public string $type = 'website',
        public bool $noindex = false,
        public ?string $keywords = null,
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
    ) {}

    public static function make(?string $title = null, ?string $description = null, MediaAsset|string|null $image = null): self
    {
        return new self(
            title: $title,
            description: $description,
            image: $image instanceof MediaAsset ? $image->safeUrl() : $image,
        );
    }

    public function noindex(bool $noindex = true): self
    {
        $this->noindex = $noindex;

        return $this;
    }

    public function fullTitle(Settings $settings): string
    {
        if (blank($this->title)) {
            return (string) $settings->get('seo_default_title', $settings->get('business_name'));
        }

        $suffix = (string) $settings->get('seo_title_suffix', '');

        return str_contains($this->title, trim($suffix, ' |')) ? $this->title : $this->title.$suffix;
    }

    public function metaDescription(Settings $settings): string
    {
        return str((string) ($this->description ?: $settings->get('seo_default_description')))->squish()->limit(160)->toString();
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?? url()->current();
    }

    public function imageUrl(Settings $settings): string
    {
        return $this->image
            ?? $settings->media('default_og_media_id')?->safeUrl()
            ?? asset('images/demo/hero-suv-mountain-road.jpg');
    }
}
