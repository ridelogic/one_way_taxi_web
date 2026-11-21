@if (isset($districts) && $districts->isNotEmpty())
{{-- Book Your Taxi Across Tamil Nadu — directly below the hero (CMS: Admin → Homepage sections; pages: Admin → District pages) --}}
<section class="home-section bg-ivory" id="tamil-nadu" aria-labelledby="districts-title">
    <div class="container-site">
        <div class="section-head">
            <div>
                <span class="kicker">{{ $section->get('eyebrow') ?: 'All 38 districts' }}</span>
                <h2 id="districts-title" class="display-2">{{ $section->title ?: 'Book Your Taxi Across Tamil Nadu' }}</h2>
                <p class="lead">{{ $section->subtitle ?: 'Explore reliable taxi services across all 38 districts of Tamil Nadu. Choose your district and book your journey with ease.' }}</p>
            </div>
            <a href="{{ route('districts.index') }}" class="btn btn-outline shrink-0">View all districts <x-icon name="arrow-right" :size="18" /></a>
        </div>
        <x-district-cards :districts="$districts" />
    </div>
</section>
@endif
