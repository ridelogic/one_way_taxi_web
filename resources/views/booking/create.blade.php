@extends('layouts.app')

@section('content')
    <x-page-banner title="Book a Taxi" subtitle="Local, Rental, One Way and Round Trip taxis. See an estimate where available — our team calls to confirm every booking." :crumbs="['Book a Taxi' => null]" />

    @php
        $fromWidget = old('source') === 'widget';
        $value = fn (string $key) => $fromWidget ? ($prefill[$key] ?? '') : old($key, $prefill[$key] ?? '');
        $err = fn (string $key) => $fromWidget ? null : $errors->first($key);
        $trip = $value('trip_type') ?: 'one_way';
    @endphp

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_380px]">
            <form method="POST" action="{{ route('booking.store') }}" class="card p-6 sm:p-8" data-quote-form data-estimate-url="{{ route('booking.estimate') }}"
                @if ($googleMapsKey) data-maps-key="{{ $googleMapsKey }}" @endif
                data-fleet-vehicles='@json((object) $fleetVehicleTypes)' data-packages-url="{{ route('booking.rental-packages') }}" novalidate>
                @csrf
                <input type="hidden" name="source" value="booking-page">
                {{-- Filled from the Google Places suggestion and Directions route; the server re-checks them. --}}
                @foreach (['pickup_lat', 'pickup_lng', 'drop_lat', 'drop_lng', 'route_km', 'route_sec', 'pickup_city'] as $hidden)
                    <input type="hidden" name="{{ $hidden }}" value="{{ old($hidden) }}" data-fleet-hidden="{{ $hidden }}">
                @endforeach

                <div class="trip-tabs w-full sm:w-auto" role="radiogroup" aria-label="Trip type">
                    @foreach (\App\Enums\TripType::cases() as $type)
                        <label class="trip-tab">
                            <input type="radio" name="trip_type" value="{{ $type->value }}" @checked($trip === $type->value) data-trip-type>
                            <span><x-icon :name="$type->icon()" :size="16" class="hidden sm:block" /> {{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>

                <p class="mt-4 text-sm text-ink-soft">
                    @foreach (\App\Enums\TripType::cases() as $type)<span data-trip-show="{{ $type->value }}" @if ($trip !== $type->value) hidden @endif><strong class="text-forest">{{ $type->label() }}:</strong> {{ $type->description() }}</span>@endforeach
                </p>

                @if (! empty($isRoundTrip))
                    <p class="mt-5 flex items-start gap-2 rounded-xl bg-mint px-4 py-3 text-sm text-ink"><x-icon name="swap" :size="18" class="mt-0.5 shrink-0 text-gold" /> Round Trip selected — add your return date. Our team confirms the final fare by phone.</p>
                @endif

                @if (! $fromWidget && $errors->any())
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">Please correct the highlighted fields below.</div>
                @endif

                <datalist id="city-options">
                    @foreach ($cities as $city)
                        <option value="{{ $city->name }}">{{ $city->suggestionHint() }}</option>
                    @endforeach
                </datalist>

                <h2 class="mt-8 text-xl font-bold">Trip details</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <x-form.field name="pickup_location" label="Pickup location" icon="map-pin" required :error="$err('pickup_location')">
                        <input id="pickup_location" type="text" name="pickup_location" list="city-options" value="{{ $value('pickup_location') }}" placeholder="Enter pickup city or area" required maxlength="255" autocomplete="off" class="form-control form-control-icon" data-estimate-input data-place="pickup">
                    </x-form.field>
                    <x-form.field name="destination_location" label="Drop location" icon="flag" required :error="$err('destination_location')" data-trip-show="local one_way outstation">
                        <input id="destination_location" type="text" name="destination_location" list="city-options" value="{{ $value('destination_location') }}" placeholder="Enter drop city or area" required maxlength="255" autocomplete="off" class="form-control form-control-icon" data-estimate-input data-place="drop">
                    </x-form.field>
                    <x-form.field name="fleet_package_id" label="Rental package" icon="clock" required :error="$err('fleet_package_id')" data-trip-show="rental" data-fleet-package-field hidden>
                        <select id="fleet_package_id" name="fleet_package_id" required disabled class="form-control form-control-icon" data-estimate-input data-selected="{{ old('fleet_package_id') }}">
                            <option value="">Select vehicle and pickup first</option>
                        </select>
                    </x-form.field>
                    <x-form.field name="rental_package" label="Rental package" icon="clock" required :error="$err('rental_package')" data-trip-show="rental" data-site-package-field>
                        <select id="rental_package" name="rental_package" required class="form-control form-control-icon">
                            <option value="">Select package</option>
                            @foreach (settings()->lines('rental_packages') as $package)
                                <option value="{{ $package }}" @selected($value('rental_package') === $package)>{{ $package }}</option>
                            @endforeach
                        </select>
                    </x-form.field>
                    <x-form.field name="vehicle_category_id" label="Vehicle category" icon="car" required :error="$err('vehicle_category_id')">
                        <select id="vehicle_category_id" name="vehicle_category_id" required class="form-control form-control-icon" data-estimate-input>
                            <option value="">Select vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected((string) $value('vehicle_category_id') === (string) $vehicle->id) @isset($fleetVehicleTypes[$vehicle->id]) data-fleet-types="{{ implode(' ', $fleetVehicleTypes[$vehicle->id]) }}" @endisset>{{ $vehicle->name }} · {{ $vehicle->passenger_capacity }} seats</option>
                            @endforeach
                        </select>
                    </x-form.field>
                    <x-form.field name="travel_date" label="Travel date" icon="calendar" required :error="$err('travel_date')">
                        <input id="travel_date" type="date" name="travel_date" value="{{ $value('travel_date') }}" min="{{ now()->toDateString() }}" max="{{ now()->addYear()->toDateString() }}" required class="form-control form-control-icon" data-estimate-input>
                    </x-form.field>
                    <x-form.field name="travel_time" label="Travel time" icon="clock" required :error="$err('travel_time')">
                        <input id="travel_time" type="time" name="travel_time" value="{{ $value('travel_time') }}" required class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="return_date" label="Return date" icon="calendar" :error="$err('return_date')" data-trip-show="outstation">
                        <input id="return_date" type="date" name="return_date" value="{{ $value('return_date') }}" min="{{ now()->toDateString() }}" class="form-control form-control-icon" data-estimate-input>
                    </x-form.field>
                    <x-form.field name="passengers" label="Passengers (optional)" icon="users" :error="$err('passengers')">
                        <input id="passengers" type="number" name="passengers" value="{{ $value('passengers') }}" min="1" max="20" inputmode="numeric" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="pickup_address" label="Pickup address (optional)" icon="home" :error="$err('pickup_address')">
                        <input id="pickup_address" type="text" name="pickup_address" value="{{ $value('pickup_address') }}" maxlength="255" placeholder="House / hotel / station" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="drop_address" label="Drop address (optional)" icon="map" :error="$err('drop_address')" data-trip-show="local one_way outstation">
                        <input id="drop_address" type="text" name="drop_address" value="{{ $value('drop_address') }}" maxlength="255" class="form-control form-control-icon">
                    </x-form.field>
                </div>

                <h2 class="mt-10 text-xl font-bold">Your details</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <x-form.field name="customer_phone" label="Mobile number" icon="smartphone" required :error="$err('customer_phone')">
                        <input id="customer_phone" type="tel" name="customer_phone" value="{{ $value('customer_phone') }}" required maxlength="20" autocomplete="tel" placeholder="10-digit mobile number" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="customer_name" label="Full name (optional)" icon="user" :error="$err('customer_name')">
                        <input id="customer_name" type="text" name="customer_name" value="{{ $value('customer_name') }}" maxlength="120" autocomplete="name" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="customer_email" label="Email (optional)" icon="mail" :error="$err('customer_email')" class="sm:col-span-2">
                        <input id="customer_email" type="email" name="customer_email" value="{{ $value('customer_email') }}" maxlength="150" autocomplete="email" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="customer_notes" label="Notes (optional)" :error="$err('customer_notes')" class="sm:col-span-2">
                        <textarea id="customer_notes" name="customer_notes" maxlength="1000" rows="3" placeholder="Flight or train number, extra stops, luggage…" class="form-control">{{ $value('customer_notes') }}</textarea>
                    </x-form.field>
                </div>

                <div class="absolute -left-[10000px]" aria-hidden="true"><label for="website">Leave this field empty</label><input id="website" type="text" name="website" tabindex="-1" autocomplete="off"></div>

                <label class="form-check mt-6">
                    <input type="checkbox" name="consent" value="1" @checked(! $fromWidget && old('consent')) required>
                    <span>I agree to the <a href="{{ url('/privacy-policy') }}" target="_blank">Privacy Policy</a> and <a href="{{ url('/terms-and-conditions') }}" target="_blank">Terms</a>, and consent to be contacted about this booking.</span>
                </label>
                @if ($m = $err('consent'))<p class="form-error">{{ $m }}</p>@endif

                <div class="quote-preview mt-5" data-estimate-output hidden aria-live="polite"></div>

                <button type="submit" class="btn btn-gold btn-lg mt-6 w-full">Submit Booking Enquiry <x-icon name="arrow-right" :size="18" /></button>
                <p class="mt-3 flex items-center gap-2 text-sm text-ink-soft"><x-icon name="lock" :size="14" class="text-fresh" /> {{ $settings->get('booking_notice') }}</p>
            </form>

            <aside class="space-y-5 lg:sticky lg:top-28">
                <x-estimate-panel :estimate="$estimate" />
                <div class="card p-6">
                    <h2 class="text-lg font-bold">Prefer to talk?</h2>
                    <p class="mt-1 text-sm text-ink-soft">Our team can help you choose a vehicle and plan your trip.</p>
                    @if ($settings->get('phone'))
                        <a href="{{ $settings->telLink() }}" class="btn btn-outline mt-4 w-full"><x-icon name="phone" :size="18" /> {{ $settings->get('phone') }}</a>
                    @endif
                </div>
            </aside>
        </div>
    </section>
@endsection
