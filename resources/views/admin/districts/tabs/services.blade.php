@extends('admin.layout')

@section('title', $district->name.' · Taxi Services')

@section('content')
    @include('admin.districts._header')

    <form method="POST" action="{{ route('admin.districts.tab.update', [$district, 'services']) }}" style="margin-top:16px">
        @csrf @method('PUT')
        <div class="layout-2">
            <section class="acard">
                <h2 class="acard__title"><x-icon name="briefcase" :size="18" /> Taxi services on the {{ $district->name }} page</h2>
                <p class="acard__help">Tick the services to show and, if you like, write a description for this district. Blank descriptions use the standard text.</p>
                @foreach (\App\Http\Requests\Admin\DistrictPageRequest::SERVICE_KEYS as $key => $label)
                    <div class="service-row">
                        <label class="acheck"><input type="checkbox" name="visible[]" value="{{ $key }}" @checked($district->serviceVisible($key))> <strong>Show {{ $label }}</strong></label>
                        <x-admin.textarea :name="'service_texts['.$key.']'" :label="$label.' description'" :value="($district->service_texts ?? [])[$key] ?? null" rows="2" />
                    </div>
                @endforeach
                <p class="acard__help">Airport Transfers only appears when the district has a nearest airport (District Details).</p>
            </section>
            <aside>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="rupee" :size="18" /> Fares</h2>
                    <p class="acard__help">Fares are the same booking fares used everywhere on the website, set per vehicle type in <a href="{{ route('admin.fares.index') }}">Vehicle Master → Fares</a>. They are shown on this district’s cars and routes.</p>
                    <table class="atable">
                        <thead><tr><th>Vehicle</th><th>Rate</th></tr></thead>
                        <tbody>
                            @foreach ($vehicles as $vehicle)
                                <tr><td>{{ $vehicle->name }}</td><td>{{ $vehicle->fareRule?->is_active ? '₹'.rtrim(rtrim($vehicle->fareRule->per_km_rate, '0'), '.').'/km' : 'Not set' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            </aside>
        </div>
        <div class="form-actions"><button type="submit" class="abtn abtn--primary">Save taxi services</button></div>
    </form>
@endsection
