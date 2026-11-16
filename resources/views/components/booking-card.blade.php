@props([
    'cities',
    'vehicles',
    'section' => null,      // ?App\Models\PageSection (homepage "booking" section)
    'prefill' => [],
    'layout' => 'compact',  // hero (wide: two-column fields) | compact (sidebars: tabs + form)
    'headingLevel' => 2,
])
@php
    $hero = $layout === 'hero';
    $cta = $section?->get('cta_label') ?: 'GET A QUOTE';
    $note = $section?->get('note') ?: 'No payment now — our team confirms by phone.';
    $uid = 'bw'.substr(md5(uniqid('', true)), 0, 6);
    // Only show validation errors / old input in the widget that was actually submitted.
    $isSubmitted = old('source') === 'widget';
    $val = fn (string $key) => $isSubmitted ? old($key, $prefill[$key] ?? '') : ($prefill[$key] ?? '');
    $err = fn (string $key) => $isSubmitted ? $errors->first($key) : null;
    $trip = $val('trip_type') ?: \App\Enums\TripType::OneWay->value;
@endphp
<div {{ $attributes->class(['card scroll-mt-28 overflow-hidden', 'rounded-[1.75rem] border-line/80 shadow-lift' => $hero, 'border-gold/30 shadow-lift' => ! $hero]) }} id="book-ride">
    <form method="POST" action="{{ route('booking.store') }}" class="{{ $hero ? 'p-4 sm:p-6' : 'p-5 sm:p-7' }}" data-quote-form data-estimate-url="{{ route('booking.estimate') }}" novalidate>
        @csrf
        <input type="hidden" name="source" value="widget">

            <div class="flex flex-col gap-4">
                <div>
                    <h{{ $headingLevel }} class="flex items-center gap-3 text-2xl font-extrabold"><span class="h-1.5 w-8 rounded-full bg-gold"></span>{{ $section?->title ?: 'Book Your Ride' }}</h{{ $headingLevel }}>
                    <p class="mt-1 text-sm text-ink-soft">Get an instant fare estimate for your trip</p>
                </div>
                <div class="trip-tabs w-full" role="radiogroup" aria-label="Trip type">
                    @foreach (\App\Enums\TripType::cases() as $type)
                        <label class="trip-tab">
                            <input type="radio" name="trip_type" value="{{ $type->value }}" @checked($trip === $type->value) data-trip-type>
                            <span>{{ $type === \App\Enums\TripType::OneWay ? 'One Way' : $type->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

        @if ($isSubmitted && $errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">Please check the highlighted fields.</div>
        @endif

        <datalist id="{{ $uid }}-cities">
            @foreach ($cities as $city)
                <option value="{{ $city->name }}">{{ $city->state }}</option>
            @endforeach
        </datalist>

        <div @class(['mt-5 grid gap-4', 'sm:grid-cols-2' => $hero])>
            <x-form.field name="pickup_location" for="{{ $uid }}-pickup" label="Pickup City" icon="map-pin" :error="$err('pickup_location')">
                <input id="{{ $uid }}-pickup" type="text" name="pickup_location" list="{{ $uid }}-cities" placeholder="Enter pickup city" autocomplete="off" required maxlength="120" value="{{ $val('pickup_location') }}" class="form-control form-control-icon" data-estimate-input>
            </x-form.field>

            <x-form.field name="destination_location" for="{{ $uid }}-destination" label="Destination City" icon="flag" :error="$err('destination_location')" data-trip-show="one_way outstation">
                <input id="{{ $uid }}-destination" type="text" name="destination_location" list="{{ $uid }}-cities" placeholder="Enter destination city" autocomplete="off" required maxlength="120" value="{{ $val('destination_location') }}" class="form-control form-control-icon" data-estimate-input>
            </x-form.field>

            @unless ($hero)
                <x-form.field name="rental_package" for="{{ $uid }}-package" label="Rental Package" icon="clock" :error="$err('rental_package')" data-trip-show="rental">
                    <select id="{{ $uid }}-package" name="rental_package" required class="form-control form-control-icon">
                        <option value="">Select package</option>
                        @foreach (settings()->lines('rental_packages') as $package)
                            <option value="{{ $package }}" @selected($val('rental_package') === $package)>{{ $package }}</option>
                        @endforeach
                    </select>
                </x-form.field>
            @endunless

            <x-form.field name="travel_date" for="{{ $uid }}-date" label="Travel Date" icon="calendar" :error="$err('travel_date')">
                <input id="{{ $uid }}-date" type="date" name="travel_date" min="{{ now()->toDateString() }}" max="{{ now()->addYear()->toDateString() }}" value="{{ $val('travel_date') }}" required class="form-control form-control-icon">
            </x-form.field>

            <x-form.field name="travel_time" for="{{ $uid }}-time" label="Travel Time" icon="clock" :error="$err('travel_time')">
                <input id="{{ $uid }}-time" type="time" name="travel_time" value="{{ $val('travel_time') }}" required class="form-control form-control-icon">
            </x-form.field>

            <x-form.field name="vehicle_category_id" for="{{ $uid }}-vehicle" label="Vehicle Category" icon="car" :error="$err('vehicle_category_id')">
                <select id="{{ $uid }}-vehicle" name="vehicle_category_id" required class="form-control form-control-icon" data-estimate-input>
                    <option value="">Select vehicle</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected((string) $val('vehicle_category_id') === (string) $vehicle->id)>{{ $vehicle->name }} · {{ $vehicle->passenger_capacity }} seats</option>
                    @endforeach
                </select>
            </x-form.field>

            <x-form.field name="customer_phone" for="{{ $uid }}-phone" label="Mobile Number" icon="smartphone" :error="$err('customer_phone')">
                <input id="{{ $uid }}-phone" type="tel" name="customer_phone" inputmode="tel" autocomplete="tel" placeholder="10-digit mobile number" maxlength="20" required value="{{ $val('customer_phone') }}" class="form-control form-control-icon">
            </x-form.field>
        </div>

        <div class="absolute -left-[10000px] size-px overflow-hidden" aria-hidden="true">
            <label for="{{ $uid }}-website">Leave this field empty</label>
            <input id="{{ $uid }}-website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <label class="form-check mt-5">
            <input type="checkbox" name="consent" value="1" @checked($isSubmitted && old('consent')) required>
            <span>I agree to the <a href="{{ url('/privacy-policy') }}" target="_blank">Privacy Policy</a> and consent to be contacted about this enquiry.</span>
        </label>
        @if ($m = $err('consent'))<p class="form-error">{{ $m }}</p>@endif

        <div class="quote-preview mt-4" data-estimate-output hidden aria-live="polite"></div>

        <button type="submit" class="btn btn-gold btn-lg mt-5 w-full text-base tracking-wide">
            {{ $cta }} <x-icon name="arrow-right" :size="20" :stroke="2.4" />
        </button>
        <p class="mt-3 flex items-center justify-center gap-2 text-sm text-ink-soft"><x-icon name="lock" :size="15" class="text-forest" /> {{ $note }}</p>
    </form>
</div>
