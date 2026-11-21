@extends('admin.layout')

@section('title', $district->name.' · Videos')

@section('content')
    @include('admin.districts._header')
    @php($here = route('admin.districts.tab', [$district, 'videos']))

    <div class="layout-2" style="margin-top:16px">
        <section class="acard" style="padding:0">
            <h2 class="acard__title" style="padding:16px 18px 0"><x-icon name="youtube" :size="18" /> Videos on the {{ $district->name }} page</h2>
            <p class="acard__help" style="padding:0 18px">Up to {{ \App\Models\PromoVideo::MAX_ACTIVE_PER_PLACEMENT }} active videos are shown, lowest order number first.</p>
            <div class="atable-wrap">
                <table class="atable atable--cards">
                    <thead><tr><th></th><th>Video</th><th>Also on</th><th>Order</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($videos as $video)
                            <tr>
                                <td><img class="thumb" src="{{ $video->thumbnailUrl() }}" alt="" loading="lazy" style="aspect-ratio:16/9;object-fit:cover"></td>
                                <td><strong>{{ $video->title }}</strong><br><small class="muted">{{ $video->categoryLabel() }}</small></td>
                                <td>@if ($video->show_on_home)<span class="abadge abadge--blue">Homepage</span>@endif @if ($video->districts()->count() > 1)<small class="muted">{{ $video->districts()->count() - 1 }} other district(s)</small>@endif</td>
                                <td>{{ $video->sort_order }}</td>
                                <td>@if ($video->is_active && $video->show_on_districts)<span class="abadge abadge--green">Shown</span>@else<span class="abadge abadge--slate">Hidden</span>@endif @if ($video->embed_status === 'blocked')<br><span class="abadge abadge--red" title="The owner has turned off embedding — shown as a Watch on YouTube link">Can't embed</span>@elseif ($video->embed_status === 'unavailable')<br><span class="abadge abadge--red" title="Private, deleted or not found — hidden from the website">Unavailable</span>@elseif ($video->embed_status !== 'ok')<br><span class="abadge abadge--amber" title="Not verified with YouTube yet — open Edit and save to check">Not checked</span>@endif</td>
                                <td class="actions">
                                    <a class="abtn abtn--sm" href="{{ $video->watchUrl() }}" target="_blank" rel="noopener">Preview</a>
                                    <form method="POST" action="{{ route('admin.videos.toggle', $video) }}" class="inline-form">@csrf @method('PATCH')<button class="abtn abtn--sm" type="submit">{{ $video->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                    <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.videos.edit', ['video' => $video, 'return_to' => $here]) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.districts.videos.detach', [$district, $video]) }}" class="inline-form" onsubmit="return confirm('Remove this video from {{ $district->name }}? It stays on any other pages it is assigned to.')">@csrf @method('DELETE')<button class="abtn abtn--sm abtn--danger" type="submit">Remove</button></form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">No videos yet. Add a YouTube link using the form.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside>
            <form method="POST" action="{{ route('admin.videos.store') }}" class="acard">
                @csrf
                <h2 class="acard__title"><x-icon name="plus" :size="18" /> Add a video</h2>
                <input type="hidden" name="return_to" value="{{ $here }}">
                <input type="hidden" name="district_ids[]" value="{{ $district->id }}">
                <input type="hidden" name="is_active" value="1">
                <x-admin.input name="youtube_url" label="YouTube link" required placeholder="https://youtu.be/…" help="Copy it from YouTube → Share. Normal, youtu.be and Shorts links work." />
                <x-admin.input name="title" label="Video title" required maxlength="150" />
                <x-admin.textarea name="description" label="Short description" rows="2" />
                <x-admin.select name="category" label="Type of video" :options="\App\Models\PromoVideo::CATEGORIES" value="promotional" />
                <fieldset class="afield">
                    <legend>Show on</legend>
                    <label class="acheck"><input type="radio" name="placement" value="districts" @checked(old('placement', 'districts') === 'districts')> {{ $district->name }} page only</label>
                    <label class="acheck"><input type="radio" name="placement" value="both" @checked(old('placement') === 'both')> {{ $district->name }} page + Homepage</label>
                </fieldset>
                <x-admin.select name="home_section" label="Homepage section (if shown on the homepage)" :options="\App\Models\PromoVideo::HOME_SECTIONS" value="featured" />
                <x-admin.input name="sort_order" label="Display order" type="number" value="0" min="0" />
                <button type="submit" class="abtn abtn--primary">Add video</button>
                <p class="acard__help" style="margin-top:10px">To show one video on several districts, add it here and then tick the other districts on its Edit screen.</p>
            </form>
        </aside>
    </div>
@endsection
