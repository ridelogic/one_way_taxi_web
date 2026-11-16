@extends('admin.layout')

@section('title', 'Fare rule · '.$vehicle->name)

@section('content')
    <a href="{{ route('admin.fares.index') }}">← Fare rules</a>
    <form method="POST" action="{{ route('admin.fares.update', $vehicle) }}" style="margin-top:12px">
        @csrf
        @method('PUT')
        <div class="layout-2">
            <div>
                <section class="acard">
                    <h3 class="acard__title">Rates (INR)</h3>
                    <div class="aform-grid aform-grid--3">
                        <x-admin.input name="per_km_rate" label="Per-km rate (₹)" type="number" step="0.01" min="0.01" :value="$rule->per_km_rate" required />
                        <x-admin.input name="minimum_km" label="Minimum chargeable km" type="number" min="0" :value="$rule->minimum_km" required />
                        <x-admin.input name="base_fare" label="Base fare (₹)" type="number" step="0.01" min="0" :value="$rule->base_fare" required />
                        <x-admin.input name="minimum_fare" label="Minimum fare (₹)" type="number" step="0.01" min="0" :value="$rule->minimum_fare" required />
                        <x-admin.input name="driver_allowance" label="Driver allowance (₹)" type="number" step="0.01" min="0" :value="$rule->driver_allowance" required />
                    </div>
                </section>
                <section class="acard">
                    @include('admin.partials.repeater', [
                        'prefix' => 'additional_charges',
                        'label' => 'Additional charges',
                        'fields' => [
                            'label' => ['label' => 'Label', 'type' => 'text'],
                            'amount' => ['label' => 'Amount (₹) — leave blank for “as applicable”', 'type' => 'text'],
                            'note' => ['label' => 'Note', 'type' => 'text'],
                        ],
                        'items' => $rule->additional_charges ?? [],
                        'max' => 15,
                    ])
                </section>
            </div>
            <aside>
                <section class="acard">
                    <x-admin.checkbox name="is_active" label="Use this fare rule for estimates" :checked="$rule->is_active ?? true" />
                    <x-admin.textarea name="notes" label="Internal notes" :value="$rule->notes" rows="4" />
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">Save fare rule</button>
            <a href="{{ route('admin.fares.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
@endsection
