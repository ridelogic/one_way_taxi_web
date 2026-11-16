<section class="section bg-mint" id="safety" aria-labelledby="safety-title">
    <div class="container-site grid items-center gap-12 lg:grid-cols-2">
        <div>
            <x-section-heading eyebrow="Safety" :title="$section->title" :subtitle="$section->subtitle" align="left" id="safety-title" />
            <ul class="grid gap-4 sm:grid-cols-2">
                @foreach ($section->items() as $item)
                    <li class="card flex gap-4 p-5">
                        <span class="icon-tile bg-gold-100 text-gold-600"><x-icon :name="$item['icon'] ?? 'shield-check'" :size="22" /></span>
                        <div><h3 class="text-base font-bold">{{ $item['title'] ?? '' }}</h3><p class="mt-1 text-sm text-ink-soft">{{ $item['text'] ?? '' }}</p></div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="overflow-hidden rounded-[2rem] border-[6px] border-white shadow-lift">
            <x-img :media="$section->media" fallback="images/demo/partner-driver.jpg" alt="" class="aspect-[5/4] w-full" />
        </div>
    </div>
</section>
