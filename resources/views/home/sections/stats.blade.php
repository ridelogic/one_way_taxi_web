@if ($section->items())
{{-- Figures come from Admin → Homepage sections → Statistics. Until the business confirms them ("verified"), they are labelled as illustrative. --}}
<section class="stats-strip" aria-labelledby="stats-title">
    <div aria-hidden="true" class="pointer-events-none absolute -top-24 left-1/4 size-80 rounded-full bg-gold/20 blur-3xl"></div>
    <div class="container-site relative">
        <h2 id="stats-title" class="sr-only">{{ $section->title ?: 'Our numbers' }}</h2>
        <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($section->items() as $stat)
                <div class="stat-item">
                    <span class="stat-item__icon" aria-hidden="true"><x-icon :name="$stat['icon'] ?? 'star'" :size="26" /></span>
                    <div class="flex flex-col-reverse">
                        <dt class="text-sm font-medium text-white/75">{{ $stat['label'] ?? '' }}</dt>
                        <dd class="font-display text-[1.9rem] leading-tight font-extrabold text-white">{{ $stat['value'] ?? '' }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>
        @unless ($section->get('is_verified'))
            <p class="mt-4 text-center text-xs text-white/55">Illustrative figures — to be confirmed by {{ $settings->get('business_name') }}.</p>
        @endunless
    </div>
</section>
@endif
