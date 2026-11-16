@extends('layouts.app')

@section('content')
    <x-page-banner :title="$section?->title ?: 'Download the App'" :subtitle="$section?->subtitle" :crumbs="['Download App' => null]" />

    <section class="section pt-12!">
        <div class="container-site max-w-3xl">
            <div class="card p-8 sm:p-10">
                @if ($section?->get('body'))<p class="text-lg leading-relaxed text-ink-soft">{{ $section->get('body') }}</p>@endif
                @if ($section?->items())
                    <ul class="mt-5 space-y-2">
                        @foreach ($section->items() as $item)
                            <li class="flex items-center gap-2 font-medium"><x-icon name="check-circle" :size="20" class="text-fresh" /> {{ $item['text'] ?? '' }}</li>
                        @endforeach
                    </ul>
                @endif
                <x-app-download class="mt-6" />
                @unless ($appLinks->available())
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('booking.create') }}" class="btn btn-gold">Book Online Now <x-icon name="arrow-right" :size="18" /></a>
                        @if ($settings->get('phone'))
                            <a href="{{ $settings->telLink() }}" class="btn btn-outline"><x-icon name="phone" :size="18" /> {{ $settings->get('phone') }}</a>
                        @endif
                    </div>
                @endunless
            </div>
        </div>
    </section>
@endsection
