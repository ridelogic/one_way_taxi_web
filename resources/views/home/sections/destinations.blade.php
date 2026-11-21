@if ($destinations->isNotEmpty())
<section class="section bg-white" id="destinations" aria-labelledby="dest-title">
    <div class="container-site">
        <x-section-heading eyebrow="Explore" :title="$section->title" :subtitle="$section->subtitle" id="dest-title" />
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($destinations as $city)
                <a href="{{ route('routes.index', ['to' => $city->id]) }}" class="group relative overflow-hidden rounded-3xl shadow-soft">
                    <x-img :media="$city->media" alt="" class="aspect-[4/5] w-full " />
                    <span class="absolute inset-0 bg-gradient-to-t from-forest-900/85 via-transparent to-transparent"></span>
                    <span class="absolute right-4 bottom-4 left-4 text-white"><span class="block font-display text-lg font-bold">{{ $city->name }}</span><span class="text-sm text-champagne">{{ $city->state }}</span></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
