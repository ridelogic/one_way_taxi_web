{{-- How it works: numbered step cards --}}
<section class="home-section bg-ivory" id="how-it-works" aria-labelledby="how-title">
    <div class="container-site">
        <x-home-heading :eyebrow="$section->get('eyebrow') ?: 'Simple booking'" :title="$section->title" :subtitle="$section->subtitle" id="how-title" />
        <ol class="grid gap-5 md:grid-cols-3">
            @foreach ($section->items() as $step)
                <li class="step-card">
                    <span class="step-card__num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="step-card__icon" aria-hidden="true"><x-icon :name="$step['icon'] ?? 'check'" :size="24" /></span>
                    <h3 class="mt-4 text-lg font-bold"><span class="sr-only">Step {{ $loop->iteration }}: </span>{{ $step['title'] ?? '' }}</h3>
                    <p class="mt-2 text-[0.95rem] leading-relaxed text-ink-soft">{{ $step['text'] ?? '' }}</p>
                </li>
            @endforeach
        </ol>
        <div class="mt-6 text-center">
            <a href="#book-ride" class="btn btn-gold">Start Your Booking <x-icon name="arrow-right" :size="18" /></a>
        </div>
    </div>
</section>
