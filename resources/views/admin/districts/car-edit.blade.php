@extends('admin.layout')

@section('title', $district->name.' · Edit car')

@section('content')
    @include('admin.districts._header')

    <form method="POST" action="{{ route('admin.district-cars.update', $car) }}" style="margin-top:16px">
        @csrf @method('PUT')
        <div class="layout-2">
            <section class="acard">
                <h2 class="acard__title"><x-icon name="car" :size="18" /> {{ $car->displayName() }}</h2>
                <x-admin.select name="vehicle_category_id" label="Vehicle type (from Vehicle Master)" :options="$vehicles->mapWithKeys(fn ($v) => [$v->id => $v->name.' ('.$v->passenger_capacity.' seats)'.($v->is_active ? '' : ' — inactive')])->all()" :value="$car->vehicle_category_id" required />
                <x-admin.input name="name" label="Car name / model shown" :value="$car->name" placeholder="e.g. Swift Dzire" help="Blank = the vehicle name." />
                <div class="aform-grid">
                    <x-admin.input name="seats" label="Seats" type="number" :value="$car->seats" min="1" max="60" help="Blank = vehicle default ({{ $car->vehicle?->passenger_capacity }})." />
                    <x-admin.input name="sort_order" label="Display order" type="number" :value="$car->sort_order" min="0" />
                </div>
                <x-admin.checkbox name="is_ac" label="Air-conditioned (AC)" :checked="$car->is_ac" />
                <x-admin.textarea name="description" label="Car description" :value="$car->description" rows="3" />
            </section>
            <aside>
                <section class="acard">
                    <x-admin.checkbox name="is_active" label="Active — show on the website" :checked="$car->is_active" />
                    <x-admin.media name="media_id" label="Car image" :value="$car->media_id" help="Blank = the vehicle’s image from Vehicle Master." />
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">Save car</button>
            <a href="{{ route('admin.districts.tab', [$district, 'cars']) }}" class="abtn">Cancel</a>
        </div>
    </form>
    <div class="danger-zone" style="margin-top:22px">
        <div><strong>Remove this car from {{ $district->name }}</strong><p>Only removes it from this district. The vehicle stays in Vehicle Master and in other districts.</p></div>
        <x-admin.delete :action="route('admin.district-cars.destroy', $car)" />
    </div>
@endsection
