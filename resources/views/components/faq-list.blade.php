@props(['faqs'])
<div {{ $attributes->class(['space-y-3']) }}>
    @foreach ($faqs as $faq)
        <details class="group card overflow-hidden open:border-gold/60" @if ($loop->first) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-display font-bold text-forest [&::-webkit-details-marker]:hidden">
                {{ $faq->question }}
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-mint text-fresh group-open:rotate-180 group-open:bg-gold group-open:text-white"><x-icon name="chevron-down" :size="18" /></span>
            </summary>
            <div class="px-5 pb-5 leading-relaxed text-ink-soft">{!! nl2br(e($faq->answer)) !!}</div>
        </details>
    @endforeach
</div>
