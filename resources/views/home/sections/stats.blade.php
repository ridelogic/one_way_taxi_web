@if ($section->items())
{{-- Figures come from Admin → Homepage sections → Statistics. Until the business confirms them ("verified"), they are labelled as illustrative. --}}
<section class="stats-band" aria-labelledby="stats-title">
    <div class="container-site">
        <h2 id="stats-title" class="sr-only">{{ $section->title ?: 'Our numbers' }}</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($section->items() as $stat)
                <div class="stat-cell">
                    <span class="stat-cell__icon" aria-hidden="true"><x-icon :name="$stat['icon'] ?? 'star'" :size="22" /></span>
                    <div class="flex flex-col-reverse">
                        <dt class="text-sm font-medium text-ink-soft">{{ $stat['label'] ?? '' }}</dt>
                        <dd class="font-display text-[2rem] leading-tight font-extrabold tracking-[-0.02em] text-forest">{{ $stat['value'] ?? '' }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>
        @unless ($section->get('is_verified'))
            <p class="border-t border-line py-3 text-center text-xs text-ink-soft">Illustrative figures — to be confirmed by {{ $settings->get('business_name') }}.</p>
        @endunless
    </div>
</section>
@endif
