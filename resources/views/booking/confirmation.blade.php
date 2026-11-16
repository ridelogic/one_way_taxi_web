@extends('layouts.app')

@section('content')
    <section class="section bg-mint">
        <div class="container-site max-w-3xl">
            <div class="card p-8 text-center sm:p-12">
                <span class="mx-auto grid size-20 place-items-center rounded-full bg-fresh text-white ring-8 ring-mint" aria-hidden="true"><x-icon name="check" :size="40" :stroke="3" /></span>
                <h1 class="mt-6 text-3xl font-extrabold sm:text-4xl">Enquiry received</h1>
                <p class="mt-3 text-lg text-ink-soft">Thank you{{ $booking->customer_name ? ', '.$booking->customer_name : '' }}. Your enquiry has been saved — please keep your reference handy.</p>
                <p class="mt-6"><span class="inline-block rounded-2xl border-2 border-dashed border-gold bg-gold-50 px-6 py-3 font-display text-2xl font-extrabold tracking-wider text-forest">{{ $booking->reference }}</span></p>
                <p class="mx-auto mt-5 max-w-xl text-sm text-ink-soft">{{ $settings->get('booking_notice') }}</p>

                <dl class="mt-8 grid gap-3 text-left sm:grid-cols-2">
                    @php
                        $rows = [
                            'Trip type' => $booking->trip_type->label().($booking->rental_package ? ' — '.$booking->rental_package : ''),
                            'Pickup' => $booking->pickupLabel(),
                        ];
                        if ($booking->trip_type !== \App\Enums\TripType::Rental) {
                            $rows['Destination'] = $booking->destinationLabel();
                        }
                        $rows['Travel'] = $booking->travel_date->format('D, j M Y').($booking->travel_time ? ' at '.\Illuminate\Support\Carbon::parse($booking->travel_time)->format('g:i A') : '').($booking->return_date ? ' · return '.$booking->return_date->format('j M') : '');
                        $rows['Vehicle'] = $booking->vehicleCategory?->name ?? 'To be recommended';
                        $rows['Estimated fare'] = $booking->estimated_fare ? inr($booking->estimated_fare) : (! empty($booking->fare_breakdown['starting_fare']) ? 'From '.inr($booking->fare_breakdown['starting_fare']) : 'Our team will share a quote');
                        $rows['Mobile'] = $booking->customer_phone;
                        $rows['Status'] = $booking->status->label().' — awaiting confirmation call';
                    @endphp
                    @foreach ($rows as $label => $value)
                        <div class="rounded-xl bg-mint px-4 py-3"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">{{ $label }}</dt><dd class="mt-1 font-semibold text-forest">{{ $value }}</dd></div>
                    @endforeach
                </dl>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('home') }}" class="btn btn-outline">Back to home</a>
                    @if ($settings->get('phone'))
                        <a href="{{ $settings->telLink() }}" class="btn btn-gold"><x-icon name="phone" :size="18" /> Call {{ $settings->get('phone') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
