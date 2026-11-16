@extends('layouts.app')

@section('content')
    <x-page-banner title="Our Services" subtitle="One-way drops, outstation trips, airport transfers, local travel, corporate travel and rentals." :crumbs="['Services' => null]" fallback="images/demo/airport-chennai.jpg" />

    <section class="section pt-12!">
        <div class="container-site grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <a href="{{ route('services.show', $service->slug) }}" class="card card-hover group flex flex-col overflow-hidden">
                    <div class="aspect-[16/10] overflow-hidden bg-mint"><x-img :media="$service->media" alt="" class="size-full transition duration-500 group-hover:scale-105" /></div>
                    <div class="flex flex-1 flex-col p-6">
                        <span class="-mt-12 grid size-14 place-items-center rounded-2xl border-4 border-white bg-gold text-white shadow-soft"><x-icon :name="$service->icon" :size="24" /></span>
                        <h2 class="mt-4 text-xl font-bold">{{ $service->title }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-soft">{{ $service->short_description }}</p>
                        <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold text-fresh">Learn more <x-icon name="arrow-right" :size="16" /></span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <x-cta-band />
@endsection
