@props(['cities', 'section' => null, 'prefill' => [], 'local' => true, 'title' => null, 'inHero' => false])
@php
    // The taxi search (CMS: Admin → Homepage sections → Booking card), used on the homepage hero and district pages.
    // It does not create a booking itself: "Search Taxi" opens the full booking page (/book) with the trip prefilled,
    // where the visitor sees the fare estimate, picks a vehicle and leaves contact details — the existing flow.
    // The four trip types (App\Enums\TripType), named the same everywhere:
    //   Local      → trip_type=local       point-to-point ride within the city
    //   Rental     → trip_type=rental      hourly / package hire (package chosen on the booking page; no drop location)
    //   One Way    → trip_type=one_way     one-way outstation journey
    //   Round Trip → trip_type=round_trip  return outstation journey (stored as 'outstation'), with a return date
    // Airport transfers are a service, not a trip type: airports are offered as One Way drop locations, written
    // "City, Airport" so the existing city matching still finds route fares.
    $title = $title ?: ($section?->title ?: 'Search for a Taxi');
    $submit = $section?->get('submit_label') ?: 'Search Taxi';
    $leadHours = max(0, (int) settings('min_hours_before_pickup', 2));
    $tabs = [
        ['value' => \App\Enums\TripType::OneWay->value, 'label' => \App\Enums\TripType::OneWay->label(), 'icon' => \App\Enums\TripType::OneWay->icon()],
        ['value' => 'round_trip', 'label' => \App\Enums\TripType::Outstation->label(), 'icon' => \App\Enums\TripType::Outstation->icon()],
        ['value' => \App\Enums\TripType::Local->value, 'label' => \App\Enums\TripType::Local->label(), 'icon' => \App\Enums\TripType::Local->icon()],
        ['value' => \App\Enums\TripType::Rental->value, 'label' => \App\Enums\TripType::Rental->label(), 'icon' => \App\Enums\TripType::Rental->icon()],
    ];
    $airports = [
        'Chennai, Chennai International Airport (MAA)', 'Coimbatore, Coimbatore International Airport (CJB)',
        'Madurai, Madurai Airport (IXM)', 'Tiruchirappalli, Tiruchirappalli International Airport (TRZ)',
        'Thoothukudi, Tuticorin Airport (TCR)', 'Salem, Salem Airport (SXV)',
        'Bengaluru, Kempegowda International Airport (BLR)', 'Puducherry, Puducherry Airport (PNY)',
        'Thiruvananthapuram, Thiruvananthapuram International Airport (TRV)',
    ];
@endphp
<div id="book-ride" class="booking-panel scroll-mt-24 {{ $inHero ? 'booking-panel--hero' : '' }}">
    <div class="{{ $inHero ? 'container-wide' : 'container-site' }}">
        <form method="GET" action="{{ route('booking.create') }}" class="booking-form booking-form__body" aria-labelledby="book-ride-title"
              data-trip-search data-lead-hours="{{ $leadHours }}">
            <div class="booking-form__head">
                <div class="booking-tabs booking-tabs--4" role="radiogroup" aria-label="Trip type">
                    @foreach ($tabs as $tab)
                        <label class="booking-tab">
                            <input type="radio" name="trip_type" value="{{ $tab['value'] }}" class="sr-only" @checked($tab['value'] === 'one_way') data-trip-type>
                            <span class="booking-tab__inner"><x-icon :name="$tab['icon']" :size="17" :stroke="2.2" /> {{ $tab['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="booking-form__intro">
                    <h2 id="book-ride-title" class="booking-form__title">{{ $title }}</h2>
                    <p class="booking-form__note">
                        <span class="inline-flex items-center gap-1.5"><x-icon name="check-circle" :size="15" class="text-gold" /> Fare estimate before you book</span>
                        <span class="inline-flex items-center gap-1.5"><x-icon name="check-circle" :size="15" class="text-gold" /> Every ride confirmed by phone</span>
                    </p>
                </div>
            </div>

            <datalist id="bp-cities">
                @foreach ($cities as $city)
                    <option value="{{ $city->name }}">{{ $city->suggestionHint() }}</option>
                @endforeach
                @foreach ($airports as $airport)
                    <option value="{{ $airport }}">Airport</option>
                @endforeach
            </datalist>

            <div class="booking-fields">
                <x-form.field name="pickup_location" for="bp-pickup" label="Pickup Location" required icon="map-pin" class="field-wide" :error="false">
                    <input id="bp-pickup" type="text" name="pickup_location" list="bp-cities" placeholder="Pickup city" autocomplete="off" required minlength="2" maxlength="120" class="form-control form-control-icon" @if (! empty($prefill['pickup_location'])) value="{{ $prefill['pickup_location'] }}" @endif>
                </x-form.field>

                <x-form.field name="destination_location" for="bp-destination" label="Drop Location" required icon="flag" class="field-wide" :error="false" data-trip-show="local one_way round_trip">
                    <input id="bp-destination" type="text" name="destination_location" list="bp-cities" placeholder="Drop city, area or airport" autocomplete="off" required minlength="2" maxlength="120" class="form-control form-control-icon">
                </x-form.field>

                <x-form.field name="travel_date" for="bp-date" label="Pickup Date" required icon="calendar" class="field-narrow" :error="false">
                    <input id="bp-date" type="date" name="travel_date" min="{{ now()->toDateString() }}" max="{{ now()->addYear()->toDateString() }}" required class="form-control form-control-icon">
                </x-form.field>

                <x-form.field name="return_date" for="bp-return" label="Return Date" required icon="calendar" class="field-narrow" :error="false" data-trip-show="round_trip" hidden>
                    <input id="bp-return" type="date" name="return_date" min="{{ now()->toDateString() }}" max="{{ now()->addYear()->toDateString() }}" required class="form-control form-control-icon" disabled>
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
