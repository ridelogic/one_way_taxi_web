@props(['cities', 'section' => null])
@php
    // The homepage booking search (CMS: Admin → Homepage sections → Booking card), on the hero's bottom edge.
    // It does not create a booking itself: "Search Taxi" opens the full booking page (/book) with the trip
    // prefilled, where the visitor sees the fare estimate, picks a vehicle and leaves contact details.
    // Round Trip is the outstation service with a return date (the booking system has no separate type).
    $title = $section?->title ?: 'Search for a Taxi';
    $submit = $section?->get('submit_label') ?: 'Search Taxi';
    $leadHours = max(0, (int) settings('min_hours_before_pickup', 2));
    $tabs = [
        ['value' => 'one_way', 'label' => 'One Way', 'icon' => 'route'],
        ['value' => 'round_trip', 'label' => 'Round Trip', 'icon' => 'swap'],
        ['value' => 'outstation', 'label' => 'Outstation', 'icon' => 'road'],
    ];
@endphp
<div id="book-ride" class="booking-panel scroll-mt-24">
    <div class="container-site">
        <h2 id="book-ride-title" class="sr-only">{{ $title }}</h2>
        <form method="GET" action="{{ route('booking.create') }}" class="booking-form booking-form__body" aria-labelledby="book-ride-title"
              data-trip-search data-lead-hours="{{ $leadHours }}">
            <div class="booking-tabs" role="radiogroup" aria-label="Trip type">
                @foreach ($tabs as $tab)
                    <label class="booking-tab">
                        <input type="radio" name="trip_type" value="{{ $tab['value'] }}" class="sr-only" @checked($loop->first) data-trip-type>
                        <span class="booking-tab__inner"><x-icon :name="$tab['icon']" :size="18" :stroke="2.2" /> {{ $tab['label'] }}</span>
                    </label>
                @endforeach
            </div>

            <datalist id="bp-cities">
                @foreach ($cities as $city)
                    <option value="{{ $city->name }}">{{ $city->state }}</option>
                @endforeach
            </datalist>

            <div class="booking-fields">
                <x-form.field name="pickup_location" for="bp-pickup" label="Pickup Location" required icon="map-pin" class="field-wide" :error="false">
                    <input id="bp-pickup" type="text" name="pickup_location" list="bp-cities" placeholder="Pickup city" autocomplete="off" required minlength="2" maxlength="120" class="form-control form-control-icon">
                </x-form.field>

                <x-form.field name="destination_location" for="bp-destination" label="Drop Location" required icon="flag" class="field-wide" :error="false">
                    <input id="bp-destination" type="text" name="destination_location" list="bp-cities" placeholder="Drop city" autocomplete="off" required minlength="2" maxlength="120" class="form-control form-control-icon">
                </x-form.field>

                <x-form.field name="travel_date" for="bp-date" label="Journey Date" required icon="calendar" class="field-narrow" :error="false">
                    <input id="bp-date" type="date" name="travel_date" min="{{ now()->toDateString() }}" max="{{ now()->addYear()->toDateString() }}" required class="form-control form-control-icon">
                </x-form.field>

                <x-form.field name="return_date" for="bp-return" label="Return Date" required icon="calendar" class="field-narrow" :error="false" data-trip-show="round_trip">
                    <input id="bp-return" type="date" name="return_date" min="{{ now()->toDateString() }}" max="{{ now()->addYear()->toDateString() }}" required class="form-control form-control-icon">
                </x-form.field>

                <x-form.field name="travel_time" for="bp-time" label="Pickup Time" required icon="clock" class="field-narrow" :error="false">
                    <input id="bp-time" type="time" name="travel_time" required class="form-control form-control-icon">
                </x-form.field>

                <button type="submit" class="booking-submit">
                    <x-icon name="search" :size="20" :stroke="2.4" /> {{ $submit }}
                </button>
            </div>
            <p class="form-error mt-3" data-search-error role="alert" hidden></p>
        </form>
    </div>
</div>
