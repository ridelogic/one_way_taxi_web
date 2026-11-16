@extends('admin.layout')

@section('title', $route->exists ? 'Edit route' : 'Add route')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('admin.routes.index') }}">← All routes</a>
            <h2 style="margin:6px 0 0">{{ $route->exists ? $route->name() : 'New route' }}</h2>
        </div>
        @if ($route->exists && $route->is_active)
            <a class="abtn" href="{{ route('routes.show', $route->slug) }}" target="_blank" rel="noopener"><x-icon name="external-link" :size="16" /> View live</a>
        @endif
    </div>

    <form method="POST" action="{{ $route->exists ? route('admin.routes.update', $route) : route('admin.routes.store') }}">
        @csrf
        @if ($route->exists) @method('PUT') @endif

        <div class="layout-2">
            <div>
                <section class="acard">
                    <h3 class="acard__title">Route</h3>
                    <div class="aform-grid">
                        <x-admin.select name="origin_city_id" label="Origin city" :options="$cities" :value="$route->origin_city_id" placeholder="Select…" required />
                        <x-admin.select name="destination_city_id" label="Destination city" :options="$cities" :value="$route->destination_city_id" placeholder="Select…" required />
                        <x-admin.input name="distance_km" label="Distance (km)" type="number" step="0.1" min="1" :value="$route->distance_km" required />
                        <x-admin.input name="duration_minutes" label="Estimated duration (minutes)" type="number" min="10" :value="$route->duration_minutes" required help="e.g. 390 = 6 hrs 30 min" />
                        <x-admin.input name="base_fare" label="Starting fare (₹)" type="number" step="0.01" min="0" :value="$route->base_fare" required help="Shown publicly as “Starting from”." />
                        <x-admin.input name="slug" label="URL slug" :value="$route->slug" help="Leave blank to generate, e.g. chennai-to-madurai-taxi." />
                    </div>
                    <div class="afield" style="margin-bottom:0"><a href="{{ route('admin.cities.create') }}" target="_blank">+ Add a new city</a></div>
                </section>

                <section class="acard">
                    <h3 class="acard__title">Route-specific fares</h3>
                    <p class="acard__help">Optional. A fare entered here replaces the distance-based calculation (per-km rate + driver allowance) for that vehicle on this route. Leave blank to use the vehicle’s fare rule. Additional charges from the fare rule still apply.</p>
                    <div class="atable-wrap">
                        <table class="atable">
                            <thead><tr><th>Vehicle</th><th>Fixed fare (₹)</th><th>Current estimate</th></tr></thead>
                            <tbody>
                                @foreach ($vehicles as $vehicle)
                                    @php($guide = $guides[$vehicle->id] ?? null)
                                    <tr>
                                        <td class="strong">{{ $vehicle->name }}</td>
                                        <td style="width:200px">
                                            <input type="number" step="0.01" min="0" name="fares[{{ $vehicle->id }}]" value="{{ old('fares.'.$vehicle->id, $fares[$vehicle->id] ?? '') }}" aria-label="Fixed fare for {{ $vehicle->name }}" class="ainput">
                                            @error('fares.'.$vehicle->id)<p class="afield__error">{{ $message }}</p>@enderror
                                        </td>
                                        <td class="muted">{{ $guide && $guide['available'] ? $guide['total_formatted'].' ('.($guide['basis'] === 'route_fare' ? 'fixed' : 'per-km').')' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="acard">
                    <h3 class="acard__title">Content &amp; SEO</h3>
                    <x-admin.input name="short_description" label="Short description" :value="$route->short_description" maxlength="255" />
                    <x-admin.rich name="description" label="Route page content" :value="$route->description" />
                    <x-admin.input name="seo_title" label="Meta title" :value="$route->seo_title" maxlength="120" />
                    <x-admin.textarea name="seo_description" label="Meta description" :value="$route->seo_description" rows="2" maxlength="300" />
                </section>
            </div>

            <aside>
                <section class="acard">
                    <h3 class="acard__title">Publishing</h3>
                    <x-admin.checkbox name="is_active" label="Published" :checked="$route->is_active" />
                    <x-admin.checkbox name="is_popular" label="Popular (shown first on the homepage)" :checked="$route->is_popular" />
                    <x-admin.checkbox name="is_sample_fare" label="Fare is a sample (label it on the site)" :checked="$route->is_sample_fare" help="Untick once the fare has been verified." />
                    <x-admin.input name="sort_order" label="Order" type="number" :value="$route->sort_order" min="0" />
                </section>
                <section class="acard">
                    <h3 class="acard__title">Thumbnail</h3>
                    <x-admin.media name="media_id" label="Route image" :value="$route->media_id" help="If empty, the destination city photo is used." />
                </section>
            </aside>
        </div>

        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $route->exists ? 'Save route' : 'Create route' }}</button>
            <a href="{{ route('admin.routes.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
@endsection
