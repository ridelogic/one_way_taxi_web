@extends('layouts.app')

@section('content')
    <x-page-banner :title="$service->title" :subtitle="$service->short_description" :media="$service->media" :crumbs="['Services' => route('services.index'), $service->title => null]" />

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_400px]">
            <div>
                @if ($service->highlights)
                    <ul class="mb-10 grid gap-3 sm:grid-cols-2">
                        @foreach ($service->highlights as $highlight)
                            <li class="flex items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 font-semibold text-forest shadow-soft"><x-icon name="check-circle" :size="20" class="text-gold" /> {{ $highlight }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="prose-site">{!! \App\Support\HtmlSanitizer::clean($service->body) !!}</div>

                @if ($popularRoutes->isNotEmpty())
                    <h2 class="mt-12 text-2xl font-bold">Popular routes</h2>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($popularRoutes as $r)
                            <li><a href="{{ route('routes.show', $r->slug) }}" class="chip">{{ $r->name() }} <span class="font-bold text-gold-600">{{ inr($r->base_fare) }}</span></a></li>
                        @endforeach
                    </ul>
                @endif

                @if ($others->isNotEmpty())
                    <h2 class="mt-10 text-2xl font-bold">Other services</h2>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($others as $other)
                            <li><a href="{{ route('services.show', $other->slug) }}" class="chip"><x-icon :name="$other->icon" :size="16" class="text-gold" /> {{ $other->title }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <aside class="lg:sticky lg:top-28">
                <x-booking-card :cities="$cities" :vehicles="$vehicles" :prefill="['trip_type' => ['local-taxi' => 'local', 'rentals' => 'rental', 'outstation-taxi' => 'outstation'][$service->slug] ?? 'one_way']" />
            </aside>
        </div>
    </section>
@endsection
