@extends('admin.layout')

@section('title', $announcement->exists ? 'Edit announcement' : 'Add announcement')

@section('content')
    <a href="{{ route('admin.announcements.index') }}">← All announcements</a>
    <form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="acard" style="margin-top:12px;max-width:860px">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif

        <x-admin.input name="title" label="Announcement title" :value="$announcement->title" required maxlength="120"
            help="Shown in yellow, e.g. “Pongal Special”. You can start with an emoji (🪔 🌾 🎉)." />
        <x-admin.textarea name="message" label="Announcement message" :value="$announcement->message" rows="2" required maxlength="255"
            help="Shown in white after the title. Plain text only — only advertise offers the business has approved." />
        <x-admin.input name="link_url" label="Promotional link (optional)" :value="$announcement->link_url" maxlength="500" placeholder="/book"
            help="Where the announcement leads when clicked: a page on this website such as /book, or a full https:// address." />

        <div class="aform-grid">
            <x-admin.input name="starts_at" label="Start date and time (optional)" type="datetime-local" :value="$announcement->starts_at?->format('Y-m-d\TH:i')"
                help="Empty: shows as soon as it is enabled." />
            <x-admin.input name="ends_at" label="End date and time (optional)" type="datetime-local" :value="$announcement->ends_at?->format('Y-m-d\TH:i')"
                help="Empty: no end. After this time it disappears automatically." />
            <x-admin.input name="sort_order" label="Display order" type="number" min="0" max="9999" :value="$announcement->sort_order"
                help="Lower numbers scroll first." />
        </div>

        {{-- Where it appears: the homepage and/or all or selected location pages (the real pages from District Management) --}}
        @php
            $currentTarget = old('target', $announcement->target());
            $selected = collect(old('district_ids', $announcement->districts?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
        @endphp
        <fieldset class="afield" style="border:0;padding:0;margin:0 0 6px">
            <legend style="font-weight:700;margin-bottom:6px">Display locations <span class="req">*</span></legend>
            @foreach (\App\Models\PromoAnnouncement::TARGETS as $value => [$label])
                <label class="acheck"><input type="radio" name="target" value="{{ $value }}" @checked($currentTarget === $value) data-announcement-target> {{ $label }}</label>
            @endforeach
            <p class="afield__help">“All location pages” covers every page in District Management ({{ $districts->count() }} now) and any page added later.</p>
            @error('target')<p class="afield__error">{{ $message }}</p>@enderror
        </fieldset>

        <div class="afield" data-announcement-locations @unless (str_contains($currentTarget, 'selected')) hidden @endunless>
            <p style="font-weight:700;margin:0 0 6px">Location pages <span class="req">*</span> <span class="muted" style="font-weight:400">(for “selected location pages”)</span></p>
            <div style="display:flex;gap:8px;margin:0 0 10px">
                <button type="button" class="abtn abtn--sm" data-check-all="district_ids[]">Select all</button>
                <button type="button" class="abtn abtn--sm abtn--ghost" data-check-none="district_ids[]">Clear</button>
            </div>
            <div style="display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(170px,1fr))">
                @foreach ($districts as $district)
                    <label class="acheck"><input type="checkbox" name="district_ids[]" value="{{ $district->id }}" @checked($selected->contains($district->id))> {{ $district->name }}@unless ($district->isTamilNadu()) <span class="muted">({{ $district->typeLabel() }})</span>@endunless</label>
                @endforeach
            </div>
            @error('district_ids')<p class="afield__error">{{ $message }}</p>@enderror
            @error('district_ids.*')<p class="afield__error">{{ $message }}</p>@enderror
        </div>

        <x-admin.checkbox name="is_enabled" label="Enabled — show on the homepage (within the dates above)" :checked="$announcement->is_enabled"
            help="Leave unticked to save as a draft. Disabled announcements never appear on the website." />

        <button type="submit" class="abtn abtn--primary">{{ $announcement->exists ? 'Save announcement' : 'Add announcement' }}</button>
        @if ($announcement->exists)
            <a href="{{ route('admin.announcements.preview', $announcement) }}" class="abtn" target="_blank" rel="noopener"><x-icon name="eye" :size="16" /> Preview</a>
        @endif
        <a href="{{ route('admin.announcements.index') }}" class="abtn">Cancel</a>
    </form>
    <script>
        // Show the location picker only for the "selected location pages" choices (the server validates either way).
        (function () {
            var box = document.querySelector('[data-announcement-locations]');
            var sync = function () {
                var checked = document.querySelector('[data-announcement-target]:checked');
                box.hidden = ! (checked && checked.value.indexOf('selected') !== -1);
            };
            document.querySelectorAll('[data-announcement-target]').forEach(function (radio) { radio.addEventListener('change', sync); });
        })();
    </script>
@endsection
