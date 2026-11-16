<section class="home-section bg-ivory" id="how-it-works" aria-labelledby="how-title">
    <div class="container-site">
        <x-home-heading :eyebrow="$section->get('eyebrow') ?: 'Simple booking'" :title="$section->title" :subtitle="$section->subtitle" id="how-title" />
        <ol class="flex flex-col items-center gap-4 md:flex-row md:items-start md:justify-center md:gap-0">
            @foreach ($section->items() as $step)
                <li class="max-w-xs flex-1 text-center">
                    <span class="step-icon" aria-hidden="true">
                        <x-icon :name="$step['icon'] ?? 'check'" :size="30" />
                        <span class="step-number">{{ $loop->iteration }}</span>
                    </span>
                    <h3 class="mt-6 text-lg font-bold"><span class="sr-only">Step {{ $loop->iteration }}: </span>{{ $step['title'] ?? '' }}</h3>
                    <p class="mx-auto mt-2 max-w-[16rem] text-sm leading-relaxed text-ink-soft">{{ $step['text'] ?? '' }}</p>
                </li>
                @unless ($loop->last)
                    <li aria-hidden="true" class="grid shrink-0 place-items-center text-gold md:mt-6 md:w-24 lg:w-32">
                        <span class="flex items-center md:w-full">
                            <span class="hidden h-0 flex-1 border-t-2 border-dashed border-gold/50 md:block"></span>
                            <x-icon name="chevron-right" :size="26" :stroke="2.6" class="rotate-90 md:rotate-0" />
                        </span>
                    </li>
                @endunless
            @endforeach
        </ol>
    </div>
</section>
