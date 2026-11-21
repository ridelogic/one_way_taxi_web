@extends('admin.layout')

@section('title', 'District Management')

@section('content')
    <div class="page-head">
        <form method="GET" class="filters" style="margin:0">
            <div class="afield"><label for="q">Find a district</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="e.g. Coimbatore"></div>
            <button class="abtn" type="submit">Search</button>
            @if ($search !== '')<a class="abtn abtn--ghost" href="{{ route('admin.districts.index') }}">Show all</a>@endif
        </form>
        <div class="page-head__actions">
            <a class="abtn" href="{{ route('districts.index') }}" target="_blank" rel="noopener"><x-icon name="external-link" :size="16" /> View on website</a>
        </div>
    </div>
    <p class="acard__help" style="margin:-6px 0 16px">Click a district to manage its page: details, videos, photos, SEO, cars, taxi services, routes and FAQs. Everything you save appears on that district’s page on the website.</p>

    <ul class="district-tiles">
        @forelse ($districts as $district)
            <li>
                <a href="{{ route('admin.districts.edit', $district) }}" class="district-tile">
                    <span class="district-tile__img">
                        @if ($img = $district->cardImage())<img src="{{ $img->safeUrl() }}" alt="" loading="lazy">@endif
                    </span>
                    <span class="district-tile__body">
                        <strong>{{ $district->name }}</strong>
                        <span class="district-tile__meta">{{ $district->videos_count }} videos · {{ $district->gallery_photos_count }} photos · {{ $district->district_vehicles_count }} cars · {{ $district->district_faqs_count }} FAQs</span>
                        <span>
                            @if ($district->is_published)<span class="abadge abadge--green">Live</span>@else<span class="abadge abadge--slate">Hidden</span>@endif
                            @unless ($district->content_reviewed)<span class="abadge abadge--amber" title="Seeded draft text — check it, then tick “Content reviewed” in District Details">Draft text</span>@endunless
                        </span>
                    </span>
                </a>
            </li>
        @empty
            <li class="empty">No districts match “{{ $search }}”.</li>
        @endforelse
    </ul>
@endsection
