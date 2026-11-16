@extends('admin.layout')

@section('title', 'Routes')

@section('content')
    <div class="page-head">
        <form method="GET" class="filters" style="margin:0">
            <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="City name"></div>
            <div class="afield">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Published</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Unpublished</option>
                </select>
            </div>
            <button class="abtn" type="submit">Filter</button>
        </form>
        <a class="abtn abtn--primary" href="{{ route('admin.routes.create') }}"><x-icon name="plus" :size="16" /> Add route</a>
    </div>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th></th><th>Route</th><th>Distance</th><th>Duration</th><th>Starting fare</th><th>Flags</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($routes as $route)
                    <tr>
                        <td>@if ($m = $route->displayMedia())<img class="thumb" src="{{ $m->safeUrl() }}" alt="" style="object-position:{{ $m->objectPosition() }}">@endif</td>
                        <td class="strong">{{ $route->name() }}<br><small class="muted">/routes/{{ $route->slug }}</small></td>
                        <td>{{ $route->distanceLabel() }}</td>
                        <td>{{ $route->durationLabel() }}</td>
                        <td class="strong">{{ inr($route->base_fare) }}</td>
                        <td>
                            @if ($route->is_popular)<span class="abadge abadge--blue">Popular</span>@endif
                            @if ($route->is_sample_fare)<span class="abadge abadge--amber">Sample fare</span>@endif
                        </td>
                        <td><x-admin.badge :on="$route->is_active" on-label="Published" off-label="Unpublished" /></td>
                        <td class="actions">
                            @if ($route->is_active)<a class="abtn abtn--sm" href="{{ route('routes.show', $route->slug) }}" target="_blank" rel="noopener">View</a>@endif
                            <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.routes.edit', $route) }}">Edit</a>
                            <x-admin.delete :action="route('admin.routes.destroy', $route)" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">{{ collect(request()->except('page'))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty() ? 'No routes match your search.' : 'No routes yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $routes->links('admin.partials.pagination') }}
@endsection
