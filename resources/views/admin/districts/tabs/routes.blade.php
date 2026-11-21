@extends('admin.layout')

@section('title', $district->name.' · Routes')

@section('content')
    @include('admin.districts._header')
    @php($routeOptions = $taxiRoutes->mapWithKeys(fn ($r) => [$r->id => ($r->origin?->name ?? '?').' → '.($r->destination?->name ?? '?')])->all())

    <section class="acard" style="margin-top:16px">
        <h2 class="acard__title"><x-icon name="route" :size="18" /> Popular routes</h2>
        <p class="acard__help">Each route becomes a “Book” link on the page. To show a fare, link it to a priced route — fares are never typed here.</p>

        @foreach ($items as $item)
            <form method="POST" action="{{ route('admin.district-routes.update', $item) }}" class="row-form">
                @csrf @method('PUT')
                <div class="row-form__fields row-form__fields--route">
                    <div class="afield"><label>From</label><input name="from_location" value="{{ $item->from_location }}" required maxlength="120"></div>
                    <div class="afield"><label>To</label><input name="to_location" value="{{ $item->to_location }}" required maxlength="120"></div>
                    <div class="afield"><label>Linked priced route (for fares)</label>
                        <select name="taxi_route_id"><option value="">No fare shown</option>@foreach ($routeOptions as $id => $label)<option value="{{ $id }}" @selected((int) $item->taxi_route_id === (int) $id)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="afield"><label>Order</label><input type="number" name="sort_order" value="{{ $item->sort_order }}" min="0"></div>
                    <div class="afield row-form__wide"><label>Description (optional)</label><input name="description" value="{{ $item->description }}" maxlength="500"></div>
                    <label class="acheck"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Active</label>
                </div>
                <div class="row-form__actions">
                    <button class="abtn abtn--sm abtn--primary" type="submit">Save</button>
                    <button class="abtn abtn--sm abtn--danger" type="submit" form="del-route-{{ $item->id }}">Delete</button>
                </div>
            </form>
            <form id="del-route-{{ $item->id }}" method="POST" action="{{ route('admin.district-routes.destroy', $item) }}" onsubmit="return confirm('Delete this route?')">@csrf @method('DELETE')</form>
        @endforeach

        <form method="POST" action="{{ route('admin.districts.routes.store', $district) }}" class="row-form row-form--new">
            @csrf
            <h3 class="acard__title">Add a route</h3>
            <div class="row-form__fields row-form__fields--route">
                <div class="afield"><label for="nr_from">From</label><input id="nr_from" name="from_location" value="{{ old('from_location', $district->headquarters) }}" required maxlength="120"></div>
                <div class="afield"><label for="nr_to">To</label><input id="nr_to" name="to_location" value="{{ old('to_location') }}" required maxlength="120"></div>
                <div class="afield"><label for="nr_link">Linked priced route (for fares)</label>
                    <select id="nr_link" name="taxi_route_id"><option value="">No fare shown</option>@foreach ($routeOptions as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select>
                </div>
                <div class="afield"><label for="nr_order">Order</label><input id="nr_order" type="number" name="sort_order" value="{{ $items->count() + 1 }}" min="0"></div>
                <div class="afield row-form__wide"><label for="nr_desc">Description (optional)</label><input id="nr_desc" name="description" maxlength="500"></div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="row-form__actions"><button class="abtn abtn--primary" type="submit">Add route</button></div>
        </form>
    </section>

    <form method="POST" action="{{ route('admin.districts.tab.update', [$district, 'routes']) }}">
        @csrf @method('PUT')
        <div class="layout-2">
            <section class="acard">
                <h2 class="acard__title"><x-icon name="map-pin" :size="18" /> Pickup &amp; drop locations</h2>
                <div class="aform-grid">
                    <x-admin.lines name="pickup_locations" label="Popular pickup locations" :value="$district->pickup_locations ?? []" help="One place per line." />
                    <x-admin.lines name="drop_locations" label="Popular drop locations" :value="$district->drop_locations ?? []" help="One place per line." />
                </div>
                <x-admin.lines name="popular_destinations" label="Nearby destinations" :value="$district->popular_destinations ?? []" help="One place per line. Each becomes a “book this trip” link." />
                <x-admin.lines name="related_districts" label="Nearby districts" :value="$district->related_districts ?? []" help="One district name per line, e.g. tiruppur." />
            </section>
            <aside>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="rupee" :size="18" /> Fare table cities</h2>
                    <p class="acard__help">Tick the booking cities in {{ $district->name }}. Their priced routes appear in the page’s fare table.</p>
                    @php($selected = collect(old('city_ids', $district->cities->pluck('id')->all()))->map(fn ($id) => (int) $id))
                    <div style="display:grid;gap:6px;max-height:320px;overflow:auto">
                        @foreach ($cities as $city)
                            <label class="acheck"><input type="checkbox" name="city_ids[]" value="{{ $city->id }}" @checked($selected->contains($city->id))> {{ $city->name }} <span class="muted">{{ $city->state }}</span></label>
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>
        <div class="form-actions"><button type="submit" class="abtn abtn--primary">Save locations</button></div>
    </form>
@endsection
