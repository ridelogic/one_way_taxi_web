@if ($services->isNotEmpty())
@php($cards = $services->take((int) ($section->get('limit') ?: 4)))
<section class="home-section bg-ivory pt-14! sm:pt-16!" id="services" aria-labelledby="services-title">
    <div class="container-site">
        <x-home-heading :eyebrow="$section->get('eyebrow') ?: 'What we offer'" :title="$section->title" :subtitle="$section->subtitle" id="services-title" />
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cards as $service)
                <li>
                    <a href="{{ route('services.show', $service->slug) }}" class="service-card group">
                        <span class="service-card__media">
                            <x-img :media="$service->media" alt="" class="size-full transition duration-500 group-hover:scale-105" />
                        </span>
                        <span class="service-card__icon" aria-hidden="true"><x-icon :name="$service->icon" :size="24" :stroke="2.1" /></span>
                        <div class="flex flex-1 flex-col px-5 pt-3 pb-5">
                            <h3 class="text-lg font-bold">{{ $service->title }}</h3>
                            <span class="mt-2 flex-1 text-sm leading-relaxed text-ink-soft">{{ $service->short_description }}</span>
                            <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-gold">Learn More <x-icon name="arrow-right" :size="16" class="transition group-hover:translate-x-1" /></span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
