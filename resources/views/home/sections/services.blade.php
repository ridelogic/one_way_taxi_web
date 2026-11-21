@if ($services->isNotEmpty())
@php($cards = $services->take((int) ($section->get('limit') ?: 4)))
{{-- Services (CMS: Admin → Website Settings → Services, "Show on homepage"): vehicle photo cards linking to each service page --}}
<section class="svc-band bg-ivory" id="services" aria-labelledby="services-title">
    <div class="container-site svc-wide">
        <div class="svc-head">
            <div class="svc-head__title">
                <span class="kicker">{{ $section->get('eyebrow') ?: 'Our services' }}</span>
                <h2 id="services-title" class="intro-title intro-title--lg">{{ $section->title }}</h2>
            </div>
            @if ($section->subtitle)
                <p class="svc-head__lead">{{ $section->subtitle }}</p>
            @endif
            <a href="{{ route('services.index') }}" class="text-link shrink-0">All services <x-icon name="arrow-right" :size="18" /></a>
        </div>

        <ul class="svc-grid">
            @foreach ($cards as $service)
                <li>
                    <a href="{{ route('services.show', $service->slug) }}" class="svc-tile">
                        <span class="svc-tile__media">
                            <x-img :media="$service->media" alt="" class="size-full" sizes="(min-width: 1024px) 300px, (min-width: 640px) 50vw, 100vw" />
                        </span>
                        <span class="svc-tile__bar">
                            <span class="svc-tile__icon" aria-hidden="true"><x-icon :name="$service->icon" :size="22" :stroke="2.1" /></span>
                            <h3 class="svc-tile__name">{{ $service->title }}</h3>
                            @if ($service->short_description)<span class="sr-only">— {{ $service->short_description }}</span>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
