@props(['title', 'subtitle' => null, 'media' => null, 'fallback' => null, 'crumbs' => []])
{{-- Inner-page banner: midnight navy with the homepage's sweeping orange/champagne arcs --}}
<section class="relative isolate overflow-hidden bg-[linear-gradient(135deg,var(--color-navy)_0%,var(--color-forest)_60%,var(--color-fresh)_140%)] text-white">
    <svg class="pointer-events-none absolute inset-y-0 right-0 -z-10 h-full w-[70%] opacity-90" viewBox="0 0 700 300" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M260 0C380 90 470 210 700 300V0Z" fill="#fff" fill-opacity="0.04" />
        <path d="M300 0C420 92 512 212 700 286" fill="none" style="stroke:var(--color-gold)" stroke-width="2.5" vector-effect="non-scaling-stroke" />
        <path d="M322 0C442 92 534 208 700 270" fill="none" style="stroke:var(--color-champagne);stroke-opacity:0.7" stroke-width="1.2" vector-effect="non-scaling-stroke" />
    </svg>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -left-24 -z-10 size-96 rounded-full bg-gold/20 blur-3xl"></div>
    <div class="container-site relative grid items-center gap-8 py-12 sm:py-16 @if ($media || $fallback) lg:grid-cols-[1.2fr_0.8fr] @endif">
        <div>
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1.5 text-sm text-white/65">
                <a href="{{ route('home') }}" class="hover:text-champagne max-sm:-my-2 max-sm:py-2">Home</a>
                @foreach ($crumbs as $label => $url)
                    <x-icon name="chevron-right" :size="14" class="text-white/40" />
                    @if ($url)
                        <a href="{{ $url }}" class="hover:text-champagne max-sm:-my-2 max-sm:py-2">{{ $label }}</a>
                    @else
                        <span aria-current="page" class="font-semibold text-champagne">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
            <h1 class="mt-4 text-4xl leading-tight font-extrabold text-white sm:text-5xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-4 max-w-2xl text-lg text-white/75">{{ $subtitle }}</p>
            @endif
            {{ $slot }}
        </div>
        @if ($media || $fallback)
            <div class="hidden overflow-hidden rounded-3xl shadow-[0_30px_60px_-30px_rgb(0_0_0/0.6)] ring-1 ring-champagne/40 lg:block">
                <x-img :media="$media" :fallback="$fallback" alt="" :priority="true" :lazy="false" class="aspect-[16/10] w-full" />
            </div>
        @endif
    </div>
</section>
