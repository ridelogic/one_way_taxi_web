@extends('layouts.app')

@section('body_class', 'page-home')

@section('content')
    @php
        $hero = $sections->get('hero');
        // The hero contains the booking form; everything else (including the statistics)
        // renders in the order set in Admin → Homepage sections.
        // The "Ready for Your Next Journey?" CTA ('cta') has been removed from the website and is never rendered.
        $flowSections = $sections->toBase()->except(['hero', 'booking', 'cta'])->sortBy('sort_order');
    @endphp

    {{-- Scrolling offers ticker: directly below the header, above the hero. Only enabled, currently valid
         announcements (Admin → Promotional Announcements); renders nothing when there are none. --}}
    <x-offer-ticker :announcements="$announcements ?? collect()" />

    <x-hero-slider
        :slides="$slides"
        :autoplay="(int) ($hero?->get('autoplay_seconds') ?? 6)"
        :trust="$hero?->items() ?? []"
        :cities="$cities"
        :booking-section="$bookingSection" />

    @foreach ($flowSections as $section)
        @includeIf('home.sections.'.$section->type, ['section' => $section, 'hero' => $hero])
    @endforeach
@endsection
