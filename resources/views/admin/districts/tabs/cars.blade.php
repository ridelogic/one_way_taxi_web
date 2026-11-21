@extends('admin.layout')

@section('title', $district->name.' · Cars')

@section('content')
    @include('admin.districts._header')

    <div class="layout-2" style="margin-top:16px">
        <section class="acard" style="padding:0">
            <h2 class="acard__title" style="padding:16px 18px 0"><x-icon name="car" :size="18" /> Cars available in {{ $district->name }}</h2>
            <p class="acard__help" style="padding:0 18px">Only the cars listed here appear on the {{ $district->name }} page. Fares come from <a href="{{ route('admin.fares.index') }}">Vehicle Master → Fares</a> for each vehicle type.</p>
            <div class="atable-wrap">
                <table class="atable atable--cards">
                    <thead><tr><th></th><th>Car</th><th>Vehicle type</th><th>Seats</th><th>Order</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($cars as $car)
                            <tr>
                                <td>@if ($img = $car->image())<img class="thumb" src="{{ $img->safeUrl() }}" alt="" loading="lazy">@endif</td>
                                <td><strong>{{ $car->displayName() }}</strong><br><small class="muted">{{ $car->is_ac ? 'AC' : 'Non-AC' }}</small></td>
                                <td>{{ $car->vehicle?->name }}@if ($car->vehicle?->fareRule?->is_active)<br><small class="muted">₹{{ rtrim(rtrim($car->vehicle->fareRule->per_km_rate, '0'), '.') }}/km</small>@endif</td>
                                <td>{{ $car->seatCount() }}</td>
                                <td>{{ $car->sort_order }}</td>
                                <td>@if ($car->is_active)<span class="abadge abadge--green">Shown</span>@else<span class="abadge abadge--slate">Hidden</span>@endif</td>
                                <td class="actions">
                                    <form method="POST" action="{{ route('admin.district-cars.toggle', $car) }}" class="inline-form">@csrf @method('PATCH')<button class="abtn abtn--sm" type="submit">{{ $car->is_active ? 'Hide' : 'Show' }}</button></form>
                                    <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.district-cars.edit', $car) }}">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">No cars added for {{ $district->name }} yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside>
            <form method="POST" action="{{ route('admin.districts.cars.store', $district) }}" class="acard" data-car-form>
                @csrf
                <h2 class="acard__title"><x-icon name="plus" :size="18" /> Add a car</h2>
                <fieldset class="afield">
                    <legend>Vehicle</legend>
                    <label class="acheck"><input type="radio" name="vehicle_mode" value="existing" @checked(old('vehicle_mode', 'existing') === 'existing')> Choose from Vehicle Master</label>
                    <label class="acheck"><input type="radio" name="vehicle_mode" value="new" @checked(old('vehicle_mode') === 'new')> Create a new vehicle</label>
                </fieldset>
                <div data-mode="existing">
                    <x-admin.select name="vehicle_category_id" label="Vehicle type" :options="$vehicles->mapWithKeys(fn ($v) => [$v->id => $v->name.' ('.$v->passenger_capacity.' seats)'.($v->is_active ? '' : ' — inactive')])->all()" placeholder="Choose a vehicle" />
                </div>
                <div data-mode="new">
                    <x-admin.input name="new_vehicle_name" label="New vehicle name" help="Added to Vehicle Master, so every district can select it. Set its fares in Vehicle Master → Fares." />
                    <x-admin.input name="new_vehicle_category" label="Vehicle category" placeholder="Sedan, SUV, MUV…" />
                </div>
                <x-admin.input name="name" label="Car name / model shown" placeholder="e.g. Swift Dzire" help="Blank = the vehicle name." />
                <div class="aform-grid">
                    <x-admin.input name="seats" label="Seats" type="number" min="1" max="60" help="Blank = vehicle default." />
                    <x-admin.input name="sort_order" label="Display order" type="number" value="0" min="0" />
                </div>
                <x-admin.checkbox name="is_ac" label="Air-conditioned (AC)" :checked="true" />
                <x-admin.media name="media_id" label="Car image" help="Blank = the vehicle’s image from Vehicle Master." />
                <x-admin.textarea name="description" label="Car description" rows="2" />
                <x-admin.checkbox name="is_active" label="Active — show on the website" :checked="true" />
                <button type="submit" class="abtn abtn--primary">Add car</button>
            </form>
        </aside>
    </div>

    <script>
        // Show the fields for "choose" or "create new" vehicle.
        (function () {
            var form = document.querySelector('[data-car-form]');
            if (!form) return;
            var sync = function () {
                var mode = (form.querySelector('input[name="vehicle_mode"]:checked') || {}).value || 'existing';
                form.querySelectorAll('[data-mode]').forEach(function (el) { el.hidden = el.getAttribute('data-mode') !== mode; });
            };
            form.querySelectorAll('input[name="vehicle_mode"]').forEach(function (r) { r.addEventListener('change', sync); });
            sync();
        })();
    </script>
@endsection
