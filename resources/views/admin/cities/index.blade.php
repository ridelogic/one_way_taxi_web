@extends('admin.layout')

@section('title', 'Cities')

@section('content')
    <div class="page-head">
        <form method="GET" class="filters" style="margin:0">
            <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="City or state"></div>
            <button class="abtn" type="submit">Search</button>
        </form>
        <a class="abtn abtn--primary" href="{{ route('admin.cities.create') }}"><x-icon name="plus" :size="16" /> Add city</a>
    </div>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th></th><th>City</th><th>State</th><th>Routes</th><th>Destination</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($cities as $city)
                    <tr>
                        <td>@if ($city->media)<img class="thumb" src="{{ $city->media->safeUrl() }}" alt="">@endif</td>
                        <td class="strong">{{ $city->name }}</td>
                        <td class="muted">{{ $city->state }}</td>
                        <td>{{ $city->departing_routes_count }} out · {{ $city->arriving_routes_count }} in</td>
                        <td>{{ $city->is_popular_destination ? 'Featured' : '—' }}</td>
                        <td><x-admin.badge :on="$city->is_active" /></td>
                        <td class="actions">
                            <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.cities.edit', $city) }}">Edit</a>
                            <x-admin.delete :action="route('admin.cities.destroy', $city)" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">{{ collect(request()->except('page'))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty() ? 'No cities match your search.' : 'No cities yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $cities->links('admin.partials.pagination') }}
@endsection
