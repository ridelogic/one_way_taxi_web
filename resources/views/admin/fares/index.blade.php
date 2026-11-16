@extends('admin.layout')

@section('title', 'Fare rules')

@section('content')
    <div class="acard">
        <h2 class="acard__title">How estimates are calculated</h2>
        <ol style="margin:0;padding-left:20px;line-height:1.8">
            <li>If a route has a <strong>fixed fare</strong> for the vehicle (Routes → edit), that fare is used.</li>
            <li>Otherwise: <strong>base fare + (distance × per-km rate)</strong>, using the minimum chargeable distance for short trips, and raised to the minimum fare if needed.</li>
            <li>The <strong>driver allowance</strong> and any additional charges with an amount are added. Charges without an amount (tolls, permits) are listed as “extra, as applicable”.</li>
            <li>The total is rounded up to the nearest ₹{{ settings('fare_round_to', 10) }} (Settings → Fare display). All amounts are in INR and calculated in whole paise.</li>
        </ol>
    </div>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th>Vehicle</th><th>Per km</th><th>Min. km</th><th>Base</th><th>Minimum fare</th><th>Driver allowance</th><th>Extra charges</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($vehicles as $vehicle)
                    @php($rule = $vehicle->fareRule)
                    <tr>
                        <td class="strong">{{ $vehicle->name }}</td>
                        @if ($rule)
                            <td>{{ inr($rule->per_km_rate) }}</td>
                            <td>{{ $rule->minimum_km }} km</td>
                            <td>{{ inr($rule->base_fare) }}</td>
                            <td>{{ inr($rule->minimum_fare) }}</td>
                            <td>{{ inr($rule->driver_allowance) }}</td>
                            <td class="muted">{{ collect($rule->charges())->pluck('label')->join(', ') ?: '—' }}</td>
                            <td><x-admin.badge :on="$rule->is_active" /></td>
                        @else
                            <td colspan="7"><span class="abadge abadge--red">Not configured — estimates unavailable for this vehicle</span></td>
                        @endif
                        <td class="actions"><a class="abtn abtn--sm abtn--primary" href="{{ route('admin.fares.edit', $vehicle) }}">{{ $rule ? 'Edit' : 'Set up' }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
