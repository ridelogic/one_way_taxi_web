@extends('layouts.app')

@section('content')
    <x-page-banner title="Our Fleet" subtitle="Clean, air-conditioned vehicles for every group size. Exact model subject to availability." :crumbs="['Fleet' => null]" fallback="images/demo/fleet-innova.jpg" />

    <section class="section pt-12!">
        <div class="container-site">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vehicles as $vehicle)
                    <x-vehicle-card :vehicle="$vehicle" :heading-level="2" />
                @endforeach
            </div>
            @if ($vehicles->isEmpty())
                <p class="text-lg text-ink-soft">No vehicles are listed yet.</p>
            @endif
        </div>
    </section>

    <x-cta-band />
@endsection
