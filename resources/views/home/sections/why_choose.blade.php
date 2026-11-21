{{-- Shown on its own only when the About section is hidden; otherwise its benefits appear beside About (home/sections/about). --}}
@unless (($sections ?? collect())->has('about'))
@php
    $learnLabel = $section->get('button_label') ?: 'Learn More';
    $learnUrl = $section->get('button_url') ?: '/about';
@endphp
{{-- Why choose us: charcoal section, chauffeur photograph beside feature rows --}}
<section class="why-block" id="why-us" aria-labelledby="why-title">
    <div class="container-site grid items-center gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-12">
        <div class="why-media mx-auto w-full max-w-[460px] lg:mx-0">
            <x-img :media="$section->media" fallback="images/demo/why-woman-passenger-smiling.jpg" alt="" class="aspect-[4/3] w-full" sizes="(min-width: 1024px) 460px, 100vw" />
            <div class="why-media__caption">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-gold text-white" aria-hidden="true"><x-icon name="badge-check" :size="20" /></span>
                <span class="text-sm leading-snug font-semibold">Courteous, verified drivers who know every highway they drive</span>
            </div>
        </div>

        <div>
            <span class="kicker kicker-light">{{ $section->get('eyebrow') ?: 'Why choose us' }}</span>
            <h2 id="why-title" class="display-2 text-white">{{ $section->title }}</h2>
            @if ($section->subtitle)
                <p class="mt-3 leading-[1.65] text-white/80">{{ $section->subtitle }}</p>
            @endif

            <ul class="why-list">
                @foreach ($section->items() as $item)
                    <li>
                        <span class="why-list__icon" aria-hidden="true"><x-icon :name="$item['icon'] ?? 'check'" :size="22" /></span>
                        <div>
                            <h3 class="text-[1.05rem] font-bold text-white">{{ $item['title'] ?? '' }}</h3>
                            <p class="mt-0.5 text-[0.93rem] leading-relaxed text-white/70">{{ $item['text'] ?? '' }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($section->get('body'))
                <p class="mt-6 leading-relaxed text-white/70">{{ $section->get('body') }}</p>
            @endif
            <a href="{{ \App\Models\HeroSlide::href($learnUrl) }}" class="btn btn-yellow mt-8">{{ $learnLabel }} <x-icon name="arrow-right" :size="18" /></a>
        </div>
    </div>
</section>
@endunless
