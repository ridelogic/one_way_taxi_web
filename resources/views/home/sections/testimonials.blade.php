@if ($testimonials->isNotEmpty())
{{-- Genuine reviews only; sample reviews appear (clearly labelled) only while demo mode is on. --}}
<section class="home-section bg-white" id="testimonials" aria-labelledby="testimonials-title">
    <div class="container-site">
        <x-home-heading :eyebrow="$section->get('eyebrow') ?: 'Testimonials'" :title="$section->title" :subtitle="$section->subtitle" id="testimonials-title" />
        @if ($testimonials->contains('is_sample', true))
            <p class="-mt-6 mb-8 text-center text-sm text-ink-soft"><x-sample-badge label="Sample reviews" /> Placeholder content — replace with genuine customer reviews in Admin → Testimonials.</p>
        @endif

        <div class="relative">
            <ul id="reviews-track" class="scroller -mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-4 sm:-mx-1 sm:px-1" tabindex="0" aria-label="Customer reviews">
                @foreach ($testimonials as $testimonial)
                    <li class="w-[85%] shrink-0 snap-start sm:w-[calc(50%-0.625rem)] lg:w-[calc(33.333%-0.84rem)]">
                        <figure class="review-card">
                            <figcaption class="flex items-center gap-3">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full bg-gold-100 font-display text-lg font-extrabold text-gold ring-4 ring-gold-50" aria-hidden="true">{{ \Illuminate\Support\Str::upper(mb_substr($testimonial->customer_name, 0, 1)) }}</span>
                                <span class="min-w-0 leading-tight">
                                    <strong class="block font-display text-forest">{{ $testimonial->customer_name }}</strong>
                                    <small class="text-ink-soft">{{ collect([$testimonial->location, $testimonial->trip])->filter()->join(' · ') }}</small>
                                </span>
                            </figcaption>
                            @if ($testimonial->rating)
                                <div class="mt-4 flex gap-0.5" role="img" aria-label="Rated {{ $testimonial->rating }} out of 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-icon name="star" :size="18" :class="$i <= $testimonial->rating ? 'fill-amber-400 text-amber-400' : 'text-line'" />
                                    @endfor
                                </div>
                            @endif
                            <blockquote class="mt-3 flex-1 leading-relaxed text-ink">“{{ $testimonial->quote }}”</blockquote>
                        </figure>
                    </li>
                @endforeach
            </ul>
            @if ($testimonials->count() > 3)
                <div class="mt-4 flex justify-center gap-3">
                    <button type="button" class="carousel-btn" data-carousel-prev="reviews-track" aria-label="Previous reviews"><x-icon name="chevron-left" :size="20" /></button>
                    <button type="button" class="carousel-btn" data-carousel-next="reviews-track" aria-label="More reviews"><x-icon name="chevron-right" :size="20" /></button>
                </div>
            @endif
        </div>
    </div>
</section>
@endif
