@extends('layouts.app')

@section('content')
    <x-page-banner title="Our Services" subtitle="Local, Rental, One Way and Round Trip taxis — plus airport transfers and corporate travel." :crumbs="['Services' => null]" fallback="images/demo/service-white-suv-indian-highway.jpg" variant="brand" />

    <section class="section pt-12!">
        <ul class="container-site grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <li>
                    <a href="{{ route('services.show', $service->slug) }}" class="svc-card">
                        <span class="svc-card__media">
                            <x-img :media="$service->media" alt="" class="size-full" sizes="(min-width: 1024px) 390px, (min-width: 640px) 50vw, 100vw" />
                            <span class="svc-card__index" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        </span>
                        <span class="svc-card__body">
                            <span class="flex items-center gap-3">
                                <span class="svc-card__icon" aria-hidden="true"><x-icon :name="$service->icon" :size="20" /></span>
                                <h2 class="text-xl font-bold">{{ $service->title }}</h2>
                            </span>
                            <span class="mt-3 flex-1 text-[0.93rem] leading-relaxed text-ink-soft">{{ $service->short_description }}</span>
                            <span class="svc-card__link">Learn more <x-icon name="arrow-right" :size="16" /></span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
