@if ($popularRoutes->isNotEmpty())
{{-- Popular routes & fares (CMS: Admin → Homepage sections → Routes; fares from Admin → Routes) --}}
<section class="home-section bg-ivory" id="routes" aria-labelledby="routes-title">
    <div class="container-site">
        <div class="section-head">
            <div>
                <span class="kicker">{{ $section->get('eyebrow') ?: 'Popular routes & fares' }}</span>
                <h2 id="routes-title" class="display-2">{{ $section->title }}</h2>
                <p class="lead">{{ $section->subtitle ?: 'Fixed one-way fares on our most travelled routes. Pay only for the distance you ride — no return charges.' }}</p>
            </div>
            @if ($section->get('button_label'))
                <a href="{{ \App\Models\HeroSlide::href($section->get('button_url') ?: '/routes') }}" class="btn btn-outline shrink-0">{{ $section->get('button_label') }} <x-icon name="arrow-right" :size="18" /></a>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.82fr_1.18fr] lg:gap-8">
            {{-- Visual: premium sedan on the highway, with the one-way promise --}}
            <div class="routes-visual">
                <x-img :media="$section->media" fallback="images/demo/routes-sedan-highway-sunset.jpg" alt="" class="absolute inset-0 size-full" sizes="(min-width: 1024px) 480px, 100vw" />
                <span class="absolute inset-0 bg-[linear-gradient(180deg,transparent_35%,color-mix(in_oklab,var(--color-navy)_92%,transparent)_100%)]" aria-hidden="true"></span>
                <div class="relative mt-auto p-6 sm:p-8">
                    <span class="kicker kicker-light">One-way pricing</span>
                    <p class="mt-3 font-display text-2xl leading-snug font-extrabold text-white sm:text-[1.7rem]">Pay for the journey you take — never the driver’s return.</p>
                    <ul class="mt-6 grid grid-cols-1 gap-3 text-sm font-semibold text-white/90 min-[420px]:grid-cols-3">
                        <li class="flex items-center gap-2"><x-icon name="check-circle" :size="18" class="text-sun" /> Doorstep pickup</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" :size="18" class="text-sun" /> Tolls explained</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" :size="18" class="text-sun" /> Confirmed by phone</li>
                    </ul>
                </div>
            </div>

            {{-- Route tickets --}}
            <div class="grid content-start gap-5 sm:grid-cols-2">
                @foreach ($popularRoutes as $route)
                    <x-route-card :route="$route" />
                @endforeach
                <a href="{{ route('booking.create') }}" class="routes-custom">
                    <span class="grid size-12 place-items-center rounded-lg bg-gold text-white" aria-hidden="true"><x-icon name="route" :size="22" /></span>
                    <span class="mt-5 block font-display text-lg font-extrabold text-forest">Travelling somewhere else?</span>
                    <span class="mt-1.5 block text-sm leading-relaxed text-ink-soft">Tell us your pickup and drop — we’ll share a fare for any route.</span>
                    <span class="text-link mt-auto pt-5">Get a fare <x-icon name="arrow-right" :size="16" /></span>
                </a>
            </div>
        </div>

        @if (settings()->bool('demo_mode') && $popularRoutes->contains('is_sample_fare', true))
            <p class="mt-6 text-xs text-ink-soft">Fares marked <span class="badge-sample">Sample</span> are demonstration values pending verification. Tolls, permits and parking extra as applicable.</p>
        @endif
    </div>
</section>
@endif
