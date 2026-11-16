@php
    $learnLabel = $section->get('button_label') ?: 'Learn More';
    $learnUrl = $section->get('button_url') ?: '/about';
@endphp
<section class="home-section bg-white" id="why-us" aria-labelledby="why-title">
    <div class="container-site grid items-center gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
        <div>
            <span class="home-heading__eyebrow">{{ $section->get('eyebrow') ?: 'Why choose us' }}</span>
            <h2 id="why-title" class="home-heading__title">{{ $section->title }}</h2>
            <span class="mt-4 block h-1 w-14 rounded-full bg-gold" aria-hidden="true"></span>
            @if ($section->subtitle)
                <p class="mt-5 text-lg leading-relaxed text-ink">{{ $section->subtitle }}</p>
            @endif
            @if ($section->get('body'))
                <p class="mt-4 leading-relaxed text-ink-soft">{{ $section->get('body') }}</p>
            @endif
            <a href="{{ \App\Models\HeroSlide::href($learnUrl) }}" class="btn btn-gold mt-7">{{ $learnLabel }} <x-icon name="arrow-right" :size="18" /></a>
        </div>
        <ul class="grid gap-4 sm:grid-cols-2 sm:gap-5">
            @foreach ($section->items() as $item)
                <li class="feature-tile">
                    <span class="feature-tile__icon" aria-hidden="true"><x-icon :name="$item['icon'] ?? 'check'" :size="24" /></span>
                    <div>
                        <h3 class="text-base font-bold">{{ $item['title'] ?? '' }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ $item['text'] ?? '' }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
