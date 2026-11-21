@extends('admin.layout')

@section('title', 'Video Promotions')

@section('content')
    <div class="page-head">
        <form method="GET" class="filters" style="margin:0">
            <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="Video title"></div>
            <div class="afield">
                <label for="placement">Show</label>
                <select id="placement" name="placement">
                    <option value="home" @selected($placement === 'home')>Homepage videos</option>
                    <option value="all" @selected($placement === 'all')>All videos (homepage + districts)</option>
                    <option value="inactive" @selected($placement === 'inactive')>Inactive videos</option>
                </select>
            </div>
            <div class="afield">
                <label for="district">District</label>
                <select id="district" name="district">
                    <option value="">Any district</option>
                    @foreach ($districts as $d)
                        <option value="{{ $d->id }}" @selected($districtId === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="abtn" type="submit">Filter</button>
        </form>
        <div class="page-head__actions">
            <a class="abtn abtn--primary" href="{{ route('admin.videos.create') }}"><x-icon name="plus" :size="16" /> Add video</a>
        </div>
    </div>
    <p class="acard__help" style="margin:-6px 0 16px">Homepage videos appear in the homepage video sections. District videos can also be managed inside each district (District Management → Videos). YouTube videos embedded on the website — nothing is uploaded here. One video can appear on the homepage and on any number of district pages. Up to {{ \App\Models\PromoVideo::MAX_ACTIVE_PER_PLACEMENT }} active videos per placement; homepage: <strong>{{ $homeActive }}</strong> active.</p>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable atable--cards">
            <thead><tr><th></th><th>Video</th><th>Category</th><th>Shown on</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($videos as $video)
                    <tr>
                        <td><img class="thumb" src="{{ $video->thumbnailUrl() }}" alt="" loading="lazy" style="aspect-ratio:16/9;object-fit:cover"></td>
                        <td><a class="strong" href="{{ route('admin.videos.edit', $video) }}">{{ $video->title }}</a><br><small class="muted">Added {{ $video->created_at?->format('j M Y') }}</small></td>
                        <td class="muted">{{ $video->categoryLabel() }}</td>
                        <td>
                            @if ($video->show_on_home)<span class="abadge abadge--blue">Homepage · {{ \App\Models\PromoVideo::HOME_SECTIONS[$video->home_section] ?? '' }}</span>@endif
                            @if ($video->show_on_districts && $video->districts->isNotEmpty())
                                <small class="muted">{{ $video->districts->count() > 3 ? $video->districts->take(3)->pluck('name')->join(', ').' +'.($video->districts->count() - 3).' more' : $video->districts->pluck('name')->join(', ') }}</small>
                            @endif
                            @if (! $video->show_on_home && ! ($video->show_on_districts && $video->districts->isNotEmpty()))<span class="muted">—</span>@endif
                        </td>
                        <td>{{ $video->sort_order }}</td>
                        <td>@if ($video->is_active)<span class="abadge abadge--green">Active</span>@else<span class="abadge abadge--slate">Inactive</span>@endif @if ($video->embed_status === 'blocked')<br><span class="abadge abadge--red" title="The owner has turned off embedding — shown as a Watch on YouTube link">Can't embed</span>@elseif ($video->embed_status === 'unavailable')<br><span class="abadge abadge--red" title="Private, deleted or not found — hidden from the website">Unavailable</span>@elseif ($video->embed_status !== 'ok')<br><span class="abadge abadge--amber" title="Not verified with YouTube yet — open Edit and save to check">Not checked</span>@endif</td>
                        <td class="actions">
                            <a class="abtn abtn--sm" href="{{ $video->watchUrl() }}" target="_blank" rel="noopener">Preview</a>
                            <form method="POST" action="{{ route('admin.videos.toggle', $video) }}" class="inline-form">
                                @csrf @method('PATCH')
                                <button type="submit" class="abtn abtn--sm">{{ $video->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.videos.edit', $video) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">{{ $search !== '' || $placement !== 'all' || $districtId ? 'No videos match the filter.' : 'No videos yet — add a YouTube link to get started.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $videos->links('admin.partials.pagination') }}
@endsection
