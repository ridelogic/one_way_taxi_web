{{-- District screen header: name, status, quick actions and the tab bar. Expects $district (with tab counts) and $tab. --}}
@php
    $counts = [
        'videos' => $district->videos_count ?? null, 'photos' => $district->gallery_photos_count ?? null,
        'cars' => $district->district_vehicles_count ?? null, 'routes' => $district->district_routes_count ?? null,
        'faqs' => $district->district_faqs_count ?? null,
    ];
@endphp
<a href="{{ route('admin.districts.index') }}">← All districts</a>
<div class="district-head">
    <div>
        <h2 class="district-head__name">{{ $district->name }}
            @if ($district->is_published)<span class="abadge abadge--green">Live on website</span>@else<span class="abadge abadge--slate">Hidden</span>@endif
        </h2>
        <p class="muted">{{ url('/tamil-nadu/'.$district->slug) }}</p>
    </div>
    <div class="district-head__actions">
        <form method="POST" action="{{ route('admin.districts.toggle', $district) }}" class="inline-form">
            @csrf @method('PATCH')
            <button type="submit" class="abtn">{{ $district->is_published ? 'Hide from website' : 'Publish on website' }}</button>
        </form>
        <a class="abtn abtn--primary" href="{{ $district->url() }}" target="_blank" rel="noopener"><x-icon name="external-link" :size="16" /> View page</a>
    </div>
</div>
<nav class="dtabs" aria-label="{{ $district->name }} sections">
    @foreach (\App\Http\Controllers\Admin\DistrictCmsController::TABS as $key => $label)
        <a href="{{ $key === 'details' ? route('admin.districts.edit', $district) : route('admin.districts.tab', [$district, $key]) }}" @class(['is-active' => $tab === $key]) @if ($tab === $key) aria-current="page" @endif>
            {{ $label }}@if (isset($counts[$key]))<span class="dtabs__count">{{ $counts[$key] }}</span>@endif
        </a>
    @endforeach
</nav>
