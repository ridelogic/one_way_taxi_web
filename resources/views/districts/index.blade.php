@extends('layouts.app')

@section('content')
    <x-page-banner title="Taxi Services Across Tamil Nadu" :subtitle="'One-way, round-trip and local taxis in all '.$districts->count().' districts of Tamil Nadu. Choose your district to see services, routes and fares.'" :crumbs="['Tamil Nadu' => null]" fallback="images/demo/service-white-suv-indian-highway.jpg" />

    <section class="section bg-ivory pt-12!" aria-labelledby="districts-title">
        <div class="container-site">
            <div class="section-head">
                <div>
                    <span class="kicker">{{ $districts->count() }} districts</span>
                    <h2 id="districts-title" class="display-2">Book a taxi in your district</h2>
                    <p class="lead">Every district page shows the services available, popular routes with fares from our live route data, common pickup points and a booking form with your district already filled in.</p>
                </div>
                <a href="{{ route('booking.create') }}" class="btn btn-gold shrink-0">Book a Taxi <x-icon name="arrow-right" :size="18" /></a>
            </div>
            <x-district-cards :districts="$districts" :heading-level="3" />
        </div>
    </section>
@endsection
