@extends('admin.layout')

@section('title', 'Fleet')

@section('content')
    <div class="page-head">
        <p>Vehicle categories shown on the Fleet page and in booking forms.</p>
        <a class="abtn abtn--primary" href="{{ route('admin.vehicles.create') }}"><x-icon name="plus" :size="16" /> Add vehicle</a>
    </div>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th></th><th>Vehicle</th><th>Capacity</th><th>Rate</th><th>Bookings</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td>@if ($vehicle->media)<img class="thumb" src="{{ $vehicle->media->safeUrl() }}" alt="" style="object-position:{{ $vehicle->media->objectPosition() }}">@endif</td>
                        <td class="strong">{{ $vehicle->name }}<br><small class="muted">{{ $vehicle->category }} · {{ $vehicle->example_models }}</small></td>
                        <td>{{ $vehicle->passenger_capacity }} seats · {{ $vehicle->luggage_capacity }} bags</td>
                        <td>@if ($vehicle->fareRule){{ inr($vehicle->fareRule->per_km_rate) }}/km @else<span class="abadge abadge--red">No fare rule</span>@endif</td>
                        <td>{{ $vehicle->bookings_count }}</td>
                        <td>{{ $vehicle->sort_order }}</td>
                        <td><x-admin.badge :on="$vehicle->is_active" /></td>
                        <td class="actions">
                            <a class="abtn abtn--sm" href="{{ route('admin.fares.edit', $vehicle) }}">Fares</a>
                            <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.vehicles.edit', $vehicle) }}">Edit</a>
                            <x-admin.delete :action="route('admin.vehicles.destroy', $vehicle)" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">No vehicles yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
