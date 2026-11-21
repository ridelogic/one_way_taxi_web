@extends('admin.layout')

@section('title', $video->exists ? 'Edit video' : 'Add video')

@section('content')
    <a href="{{ $returnTo ?: route('admin.videos.index') }}">← Back</a>
    <form method="POST" action="{{ $video->exists ? route('admin.videos.update', $video) : route('admin.videos.store') }}" style="margin-top:12px">
        @csrf
        @if ($video->exists) @method('PUT') @endif
        @if ($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
        @php($currentPlacement = old('placement', $video->exists ? ($video->show_on_home && $video->show_on_districts ? 'both' : ($video->show_on_home ? 'home' : 'districts')) : 'home'))
        <div class="layout-2">
            <div>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="youtube" :size="18" /> YouTube video</h2>
                    <x-admin.input name="youtube_url" label="YouTube link" :value="$video->youtube_url" required placeholder="https://www.youtube.com/watch?v=… · https://youtu.be/… · https://youtube.com/shorts/…" data-youtube-input help="Paste the link from YouTube (Share → Copy link). Standard, youtu.be and Shorts links all work. The video ID and thumbnail are picked up automatically." />
                    @error('youtube_id')<p class="afield__error" style="margin-top:-8px">{{ $message }}</p>@enderror
                    @php($statusText = ['ok' => 'Public video — it can be played on the website.', 'blocked' => 'The owner has turned off embedding — it cannot be played on the website.', 'unavailable' => 'Private, deleted or not found on YouTube.', 'unknown' => 'Not verified with YouTube yet.'])
                    <div class="yt-check" data-yt-check data-check-url="{{ route('admin.videos.check') }}">
                        <img data-youtube-thumb src="{{ $video->youtube_id ? 'https://i.ytimg.com/vi/'.$video->youtube_id.'/hqdefault.jpg' : '' }}" alt="" class="yt-check__thumb" @unless ($video->youtube_id) hidden @endunless>
                        <div>
                            <p class="yt-check__status yt-check__status--{{ $video->embed_status ?: 'idle' }}" data-youtube-status>
                                @if ($video->youtube_id)
                                    {{ $statusText[$video->embed_status] ?? 'Not verified with YouTube yet — saving checks it.' }}
                                @else
                                    Paste a link — it is checked with YouTube automatically.
                                @endif
                            </p>
                            <p class="muted" data-youtube-id style="margin:4px 0 0;font-size:.8rem">{{ $video->youtube_id ? 'Video ID: '.$video->youtube_id : '' }}</p>
                        </div>
                    </div>
                    {{-- Preview player: the admin pages send no referrer by default, which YouTube rejects — this player sends the site origin. --}}
                    <div class="yt-preview" data-yt-preview @unless ($video->youtube_id && $video->canEmbed()) hidden @endunless>
                        @if ($video->youtube_id && $video->canEmbed())
                            <iframe src="{{ $video->embedUrl(false) }}" title="Preview: {{ $video->title }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
                        @endif
                    </div>
                </section>

                <section class="acard">
                    <h2 class="acard__title"><x-icon name="file-text" :size="18" /> Details</h2>
                    <x-admin.input name="title" label="Video title" :value="$video->title" required maxlength="150" />
                    <x-admin.textarea name="description" label="Short description" :value="$video->description" rows="3" help="One or two sentences shown under the title (max 500 characters)." />
                    <x-admin.select name="category" label="Category" :options="\App\Models\PromoVideo::CATEGORIES" :value="$video->category" required />
                    <p class="acard__help">Customer testimonial videos must feature real customers who agreed to appear.</p>
                </section>

                <section class="acard">
                    <h2 class="acard__title"><x-icon name="map-pin" :size="18" /> Where to show</h2>
                    <fieldset class="afield">
                        <legend>Show this video on</legend>
                        <label class="acheck"><input type="radio" name="placement" value="home" @checked($currentPlacement === 'home')> Homepage only</label>
                        <label class="acheck"><input type="radio" name="placement" value="districts" @checked($currentPlacement === 'districts')> Selected districts only</label>
                        <label class="acheck"><input type="radio" name="placement" value="both" @checked($currentPlacement === 'both')> Homepage + selected districts</label>
                    </fieldset>
                    <x-admin.select name="home_section" label="Homepage section" :options="\App\Models\PromoVideo::HOME_SECTIONS" :value="$video->home_section ?: 'featured'" />
                    <p class="acard__help" style="margin-top:12px"><strong>Districts</strong> (used when “selected districts” is chosen):</p>
                    @php($selected = collect(old('district_ids', $video->districts?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id))
                    <div style="display:flex;gap:8px;margin:10px 0">
                        <button type="button" class="abtn abtn--sm" data-check-all="district_ids[]">Select all</button>
                        <button type="button" class="abtn abtn--sm abtn--ghost" data-check-none="district_ids[]">Clear</button>
                    </div>
                    <div style="display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(170px,1fr))">
                        @foreach ($districts as $district)
                            <label class="acheck"><input type="checkbox" name="district_ids[]" value="{{ $district->id }}" @checked($selected->contains($district->id))> {{ $district->name }}</label>
                        @endforeach
                    </div>
                    @error('district_ids')<p class="afield__error">{{ $message }}</p>@enderror
                    @error('district_ids.*')<p class="afield__error">{{ $message }}</p>@enderror
                </section>
            </div>

            <aside>
                <section class="acard">
                    <x-admin.checkbox name="is_active" label="Active (visible on the website)" :checked="$video->is_active" />
                    @error('show_on_home')<p class="afield__error">{{ $message }}</p>@enderror
                    <x-admin.input name="sort_order" label="Display order" type="number" :value="$video->sort_order" min="0" help="Lower numbers show first." />
                    <x-admin.media name="thumbnail_media_id" label="Custom thumbnail (optional)" :value="$video->thumbnail_media_id" help="Leave empty to use YouTube’s own thumbnail." />
                    @if ($video->exists)
                        <p class="acard__help">Added {{ $video->created_at?->format('j M Y, g:i a') }}</p>
                    @endif
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $video->exists ? 'Save video' : 'Add video' }}</button>
            <a href="{{ $returnTo ?: route('admin.videos.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
    @if ($video->exists)
        <div class="danger-zone" style="margin-top:22px">
            <div><strong>Delete this video</strong><p>Removes it from the homepage and every district page. The video itself stays on YouTube.</p></div>
            <x-admin.delete :action="route('admin.videos.destroy', $video)" />
        </div>
    @endif

    <script>
        (function () {
            // Live check: as a link is pasted, ask the server (which asks YouTube) whether it can be played on the site.
            var input = document.querySelector('[data-youtube-input]');
            var box = document.querySelector('[data-yt-check]');
            var thumb = document.querySelector('[data-youtube-thumb]');
            var status = document.querySelector('[data-youtube-status]');
            var idOut = document.querySelector('[data-youtube-id]');
            var preview = document.querySelector('[data-yt-preview]');
            var timer = null, seq = 0;
            var show = function (state, text) { status.className = 'yt-check__status yt-check__status--' + state; status.textContent = text; };
            var check = function () {
                var url = input.value.trim();
                if (!url) { thumb.hidden = true; preview.hidden = true; preview.innerHTML = ''; idOut.textContent = ''; show('idle', 'Paste a link — it is checked with YouTube automatically.'); return; }
                var mine = ++seq;
                show('checking', 'Checking with YouTube…');
                fetch(box.getAttribute('data-check-url') + '?url=' + encodeURIComponent(url), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (mine !== seq) return;
                        idOut.textContent = data.id ? 'Video ID: ' + data.id + (data.title ? ' · ' + data.title : '') : '';
                        thumb.hidden = !data.id; if (data.id) thumb.src = data.thumbnail;
                        show(data.status, data.message);
                        preview.innerHTML = '';
                        preview.hidden = !data.embed;
                        if (data.embed) {
                            var frame = document.createElement('iframe');
                            frame.src = data.embed; frame.title = 'Preview'; frame.loading = 'lazy';
                            frame.referrerPolicy = 'strict-origin-when-cross-origin';
                            frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'; frame.allowFullscreen = true;
                            preview.appendChild(frame);
                        }
                    })
                    .catch(function () { if (mine === seq) show('unknown', 'Could not check the link right now — it is checked again when you save.'); });
            };
            if (input && box) input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(check, 500); });
            document.querySelectorAll('[data-check-all],[data-check-none]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var on = btn.hasAttribute('data-check-all');
                    var name = btn.getAttribute(on ? 'data-check-all' : 'data-check-none');
                    document.querySelectorAll('input[name="' + name + '"]').forEach(function (box) { box.checked = on; });
                });
            });
        })();
    </script>
@endsection
