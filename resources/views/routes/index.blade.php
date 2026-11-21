@extends('layouts.app')

@section('content')
    <x-page-banner title="Routes & Fares" subtitle="One-way taxi routes with distances, travel times and starting fares." :crumbs="['Routes' => null]" fallback="images/demo/routes-sedan-highway-sunset.jpg" variant="brand" />

    <section class="section bg-ivory pt-10!">
        <div class="container-site">
            <form method="GET" action="{{ route('routes.index') }}" class="mb-10 grid gap-4 rounded-xl border-t-4 border-gold bg-white p-5 shadow-soft sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end sm:p-6" role="search">
                <x-form.field name="from" label="From" icon="map-pin">
                    <select id="from" name="from" class="form-control form-control-icon">
                        <option value="">Any city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected($from === $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-form.field name="to" label="To" icon="flag">
                    <select id="to" name="to" class="form-control form-control-icon">
                        <option value="">Any city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected($to === $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <button type="submit" class="btn btn-gold"><x-icon name="search" :size="18" /> Find routes</button>
                @if ($from || $to)
                    <a href="{{ route('routes.index') }}" class="btn btn-outline">Clear</a>
                @endif
            </form>

            @if ($routes->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($routes as $route)
                        <x-route-card :route="$route" :heading-level="2" />
                    @endforeach
                </div>
                @if (settings()->bool('demo_mode') && $routes->contains('is_sample_fare', true))
                    <p class="mt-6 text-sm text-ink-soft">Fares marked <span class="badge-sample">Sample</span> are demonstration values pending verification. Tolls, permits and parking extra as applicable.</p>
                @endif
                {{ $routes->links() }}
            @else
                <div class="card p-10 text-center">
                    <h2 class="text-xl font-bold">No routes match your search</h2>
                    <p class="mt-2 text-ink-soft">We may still be able to help. <a class="font-semibold text-forest underline" href="{{ route('booking.create') }}">Request a quote</a> and our team will get back to you.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
