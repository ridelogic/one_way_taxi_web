@extends('layouts.app')

@section('content')
    @if ($preview)
        <div class="sticky top-0 z-[60] bg-gold px-4 py-2.5 text-center text-sm font-bold text-white">Preview — this page is {{ $page->isPublished() ? 'published' : 'a draft and not visible to the public' }}.</div>
    @endif

    <x-page-banner :title="$page->heading ?: $page->title" :subtitle="$page->subheading" :media="$page->template === 'legal' ? null : $page->heroMedia" :crumbs="[$page->title => null]" :variant="$page->slug === 'about' ? 'brand' : null" />

    <section class="section pt-12!">
        @if ($page->template === 'about')
            @php($why = $homeSections->get('why_choose'))
            @php($about = $homeSections->get('about'))
            <div class="container-site grid items-start gap-12 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
                <div class="prose-site">{!! \App\Support\HtmlSanitizer::clean($page->body) !!}</div>
                <figure class="about-page-photo m-0 lg:sticky lg:top-28">
                    <div class="about-page-photo__frame overflow-hidden rounded-2xl">
                        {{-- About page photo: a smiling young Indian man in a smart suit seated in a car (its own image; the homepage About photo is unchanged) --}}
                        @php($aboutPhoto = \App\Models\MediaAsset::where('disk', 'demo')->where('path', 'about-man-car-4x3.jpg')->first())
                        <x-img :media="$aboutPhoto" fallback="images/demo/about-man-car-4x3.jpg" alt="" class="about-page-photo__img" />
                    </div>
                    <figcaption class="mt-4 flex items-center gap-3 text-sm font-semibold text-ink"><span class="h-0.5 w-7 bg-gold" aria-hidden="true"></span> Professional drivers, every ride confirmed personally</figcaption>
                </figure>
            </div>

            @if (($stats = $homeSections->get('stats')) && $stats->items())
                <div class="container-site mt-16">
                    <dl class="relative grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($stats->items() as $stat)
                            <div class="card p-6 text-center"><dt class="text-sm text-ink-soft">{{ $stat['label'] ?? '' }}</dt><dd class="mt-1 font-display text-3xl font-extrabold text-gold-600">{{ $stat['value'] ?? '' }}</dd></div>
                        @endforeach
                        @unless ($stats->get('is_verified'))<x-sample-badge label="Sample figures" class="absolute -top-3 right-3" />@endunless
                    </dl>
                </div>
            @endif

            @if ($why && $why->items())
                <div class="container-site mt-20">
                    <x-section-heading eyebrow="Why choose us" :title="$why->title" :subtitle="$why->subtitle" />
                    <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($why->items() as $item)
                            <li class="card flex gap-4 p-6">
                                <span class="icon-tile"><x-icon :name="$item['icon'] ?? 'check'" :size="22" /></span>
                                <div><h3 class="text-base font-bold">{{ $item['title'] ?? '' }}</h3><p class="mt-1 text-sm text-ink-soft">{{ $item['text'] ?? '' }}</p></div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <div class="container-site max-w-3xl">
                <article class="prose-site">{!! \App\Support\HtmlSanitizer::clean($page->body) !!}</article>
                @if ($page->template === 'legal')
                    <p class="mt-10 border-t border-line pt-5 text-sm text-ink-soft">Last updated {{ $page->updated_at->format('j F Y') }}</p>
                @endif
            </div>
        @endif
    </section>
@endsection
