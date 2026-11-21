@if ($testimonials->isNotEmpty())
{{-- Genuine reviews only; sample reviews appear (clearly labelled) only while demo mode is on. --}}
<section class="home-section bg-ivory" id="testimonials" aria-labelledby="testimonials-title">
    <div class="container-site">
        <div class="section-head">
            <div>
                <span class="kicker">{{ $section->get('eyebrow') ?: 'Testimonials' }}</span>
                <h2 id="testimonials-title" class="display-2">{{ $section->title }}</h2>
                @if ($section->subtitle)<p class="lead">{{ $section->subtitle }}</p>@endif
            </div>
            @if ($testimonials->count() > 3)
                <div class="flex shrink-0 gap-3">
                    <button type="button" class="carousel-btn" data-carousel-prev="reviews-track" aria-label="Previous reviews"><x-icon name="chevron-left" :size="20" /></button>
                    <button type="button" class="carousel-btn" data-carousel-next="reviews-track" aria-label="More reviews"><x-icon name="chevron-right" :size="20" /></button>
                </div>
            @endif
        </div>
        @if ($testimonials->contains('is_sample', true))
            <p class="-mt-2 mb-5 text-sm text-ink-soft"><x-sample-badge label="Sample reviews" /> Placeholder content — replace with genuine customer reviews in Admin → Testimonials.</p>
        @endif

        <ul id="reviews-track" class="scroller -mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-4 sm:-mx-1 sm:px-1 lg:gap-6" tabindex="0" aria-label="Customer reviews">
            @foreach ($testimonials as $testimonial)
                <li class="w-[85%] shrink-0 snap-start sm:w-[calc(50%-0.625rem)] lg:w-[calc(33.333%-1rem)]">
                    <figure class="review-card">
                        <div class="flex items-start justify-between gap-4">
                            <span class="review-card__mark" aria-hidden="true">“</span>
                            @if ($testimonial->rating)
                                <div class="flex gap-0.5" role="img" aria-label="Rated {{ $testimonial->rating }} out of 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-icon name="star" :size="17" :class="$i <= $testimonial->rating ? 'fill-sun text-sun' : 'text-line'" />
                                    @endfor
                                </div>
                            @endif
                        </div>
                        <blockquote class="mt-4 flex-1 text-[0.98rem] leading-[1.65] text-ink">{{ $testimonial->quote }}</blockquote>
                        <figcaption class="mt-5 flex items-center gap-3 border-t border-line pt-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-forest font-display text-base font-extrabold text-sun" aria-hidden="true">{{ \Illuminate\Support\Str::upper(mb_substr($testimonial->customer_name, 0, 1)) }}</span>
                            <span class="min-w-0 leading-tight">
                                <strong class="block font-display text-forest">{{ $testimonial->customer_name }}</strong>
                                <small class="text-ink-soft">{{ collect([$testimonial->location, $testimonial->trip])->filter()->join(' · ') }}</small>
                            </span>
                        </figcaption>
                    </figure>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
