@extends('layouts.app')

@section('body_class', 'page-home')

@section('content')
    @php
        $hero = $sections->get('hero');
        // The hero contains the booking form; everything else (including the statistics)
        // renders in the order set in Admin → Homepage sections.
        $flowSections = $sections->toBase()->except(['hero', 'booking'])->sortBy('sort_order');
    @endphp

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
