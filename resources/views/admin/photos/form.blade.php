@extends('admin.layout')

@section('title', 'Edit photo')

@section('content')
    <a href="{{ $returnTo ?: route('admin.photos.index') }}">← Back</a>
    @php($currentPlacement = old('placement', $photo->show_on_home && $photo->show_on_districts ? 'both' : ($photo->show_on_home ? 'home' : 'districts')))
    @php($selected = collect(old('district_ids', $photo->districts->pluck('id')->all()))->map(fn ($id) => (int) $id))
    <form method="POST" action="{{ route('admin.photos.update', $photo) }}" style="margin-top:12px">
        @csrf @method('PUT')
        @if ($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
        <div class="layout-2">
            <div>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="image" :size="18" /> Photo details</h2>
                    @if ($photo->media)<img src="{{ $photo->media->safeUrl() }}" alt="" style="max-width:100%;max-height:320px;border-radius:10px;display:block;margin-bottom:14px">@endif
                    <x-admin.input name="title" label="Photo title" :value="$photo->title" maxlength="150" />
                    <x-admin.textarea name="description" label="Photo description" :value="$photo->description" rows="3" />
                </section>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="map-pin" :size="18" /> Where to show</h2>
                    <fieldset class="afield">
                        <legend>Show this photo on</legend>
                        <label class="acheck"><input type="radio" name="placement" value="home" @checked($currentPlacement === 'home')> Homepage only</label>
                        <label class="acheck"><input type="radio" name="placement" value="districts" @checked($currentPlacement === 'districts')> Selected districts only</label>
                        <label class="acheck"><input type="radio" name="placement" value="both" @checked($currentPlacement === 'both')> Homepage + selected districts</label>
                    </fieldset>
                    <div style="display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(170px,1fr))">
                        @foreach ($districts as $district)
                            <label class="acheck"><input type="checkbox" name="district_ids[]" value="{{ $district->id }}" @checked($selected->contains($district->id))> {{ $district->name }}</label>
                        @endforeach
                    </div>
                    @error('district_ids')<p class="afield__error">{{ $message }}</p>@enderror
                </section>
            </div>
            <aside>
                <section class="acard">
                    <x-admin.checkbox name="is_active" label="Active — show on the website" :checked="$photo->is_active" />
                    <x-admin.input name="sort_order" label="Display order" type="number" :value="$photo->sort_order" min="0" help="Lower numbers show first." />
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">Save photo</button>
            <a href="{{ $returnTo ?: route('admin.photos.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
    <div class="danger-zone" style="margin-top:22px">
        <div><strong>Delete this photo</strong><p>Removes it from the homepage and every district gallery. The image file stays in Website Settings → Image library.</p></div>
        <form method="POST" action="{{ route('admin.photos.destroy', $photo) }}" onsubmit="return confirm('Delete this photo from all galleries?')">
            @csrf @method('DELETE')
            @if ($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
            <button type="submit" class="abtn abtn--danger">Delete photo</button>
        </form>
    </div>
@endsection
