@extends('layouts.app')

@section('content')
    <x-page-banner :title="$vehicle->name" :subtitle="$vehicle->short_description" :crumbs="['Fleet' => route('fleet.index'), $vehicle->name => null]" />

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_400px]">
            <div>
                <div class="overflow-hidden rounded-[2rem] border-[6px] border-white bg-mint shadow-lift">
                    <x-img :media="$vehicle->media" :alt="$vehicle->name" :lazy="false" :priority="true" class="aspect-[16/10] w-full" />
                </div>
                <dl class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="card p-4"><dt class="flex items-center gap-2 text-sm text-ink-soft"><x-icon name="users" :size="16" class="text-fresh" /> Passengers</dt><dd class="mt-1 font-display text-2xl font-extrabold text-forest">{{ $vehicle->passenger_capacity }}</dd></div>
                    <div class="card p-4"><dt class="flex items-center gap-2 text-sm text-ink-soft"><x-icon name="briefcase" :size="16" class="text-fresh" /> Luggage</dt><dd class="mt-1 font-display text-2xl font-extrabold text-forest">{{ $vehicle->luggage_capacity }} bags</dd></div>
                    @if ($vehicle->category)
                        <div class="card p-4"><dt class="flex items-center gap-2 text-sm text-ink-soft"><x-icon name="car" :size="16" class="text-fresh" /> Category</dt><dd class="mt-1 font-display text-2xl font-extrabold text-forest">{{ $vehicle->category }}</dd></div>
                    @endif
                    @if ($vehicle->fareRule)
                        <div class="card p-4"><dt class="flex items-center gap-2 text-sm text-ink-soft"><x-icon name="rupee" :size="16" class="text-fresh" /> Rate <x-sample-badge /></dt><dd class="mt-1 font-display text-2xl font-extrabold text-gold-600">{{ inr($vehicle->fareRule->per_km_rate) }}/km</dd></div>
                    @endif
                </dl>

                @if ($vehicle->example_models)
                    <p class="mt-8 text-lg"><strong class="text-forest">Typical models:</strong> {{ $vehicle->example_models }}</p>
                @endif
                @if ($vehicle->description)
                    <p class="mt-4 leading-relaxed text-ink-soft">{!! nl2br(e($vehicle->description)) !!}</p>
                @endif
                @if ($vehicle->features)
                    <h2 class="mt-8 text-xl font-bold">Features</h2>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($vehicle->features as $feature)
                            <li class="flex items-center gap-3 rounded-xl bg-mint px-4 py-3 font-medium"><x-icon name="check-circle" :size="20" class="text-fresh" /> {{ $feature }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($others->isNotEmpty())
                    <h2 class="section-title mt-14 text-2xl! sm:text-3xl!">Other vehicles</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($others as $other)
                            <x-vehicle-card :vehicle="$other" />
                        @endforeach
                    </div>
                @endif
            </div>
            <aside class="lg:sticky lg:top-28">
                <x-booking-card :cities="$cities" :vehicles="$vehicles" :prefill="['vehicle_category_id' => $vehicle->id]" />
            </aside>
        </div>
    </section>
@endsection
