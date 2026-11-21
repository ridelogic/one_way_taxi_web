@extends('admin.layout')

@section('title', $district->name.' · Photos')

@section('content')
    @include('admin.districts._header')
    @php($here = route('admin.districts.tab', [$district, 'photos']))

    <div class="layout-2" style="margin-top:16px">
        <section class="acard">
            <h2 class="acard__title"><x-icon name="image" :size="18" /> {{ $district->name }} photo gallery</h2>
            @if ($photos->isEmpty())
                <p class="empty">No photos yet. Upload some using the form.</p>
            @else
                <ul class="photo-grid">
                    @foreach ($photos as $photo)
                        <li @class(['photo-grid__item', 'is-hidden' => ! ($photo->is_active && $photo->show_on_districts)])>
                            @if ($photo->media)<img src="{{ $photo->media->safeUrl() }}" alt="" loading="lazy">@endif
                            <div class="photo-grid__body">
                                <strong>{{ $photo->title ?: 'Untitled photo' }}</strong>
                                <small class="muted">Order {{ $photo->sort_order }} · {{ $photo->is_active ? 'Shown' : 'Hidden' }}@if ($photo->show_on_home) · Homepage @endif</small>
                                <div class="photo-grid__actions">
                                    <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.photos.edit', ['photo' => $photo, 'return_to' => $here]) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.photos.toggle', $photo) }}" class="inline-form">@csrf @method('PATCH')<button class="abtn abtn--sm" type="submit">{{ $photo->is_active ? 'Hide' : 'Show' }}</button></form>
                                    <form method="POST" action="{{ route('admin.districts.photos.detach', [$district, $photo]) }}" class="inline-form" onsubmit="return confirm('Remove this photo from {{ $district->name }}?')">@csrf @method('DELETE')<button class="abtn abtn--sm abtn--danger" type="submit">Remove</button></form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside>
            <form method="POST" action="{{ route('admin.photos.store') }}" enctype="multipart/form-data" class="acard">
                @csrf
                <h2 class="acard__title"><x-icon name="upload" :size="18" /> Upload photos</h2>
                <input type="hidden" name="return_to" value="{{ $here }}">
                <input type="hidden" name="district_ids[]" value="{{ $district->id }}">
                <input type="hidden" name="is_active" value="1">
                <div @class(['afield', 'has-error' => $errors->has('files') || $errors->has('files.*')])>
                    <label for="f_files">Photos <span class="req">*</span></label>
                    <input id="f_files" type="file" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                    <p class="afield__help">Select one or more photos (up to 20 at a time, 5 MB each).</p>
                </div>
                <x-admin.input name="title" label="Photo title" help="Used for every photo in this upload — you can change each one afterwards." />
                <x-admin.textarea name="description" label="Photo description" rows="2" />
                <fieldset class="afield">
                    <legend>Show on</legend>
                    <label class="acheck"><input type="radio" name="placement" value="districts" @checked(old('placement', 'districts') === 'districts')> {{ $district->name }} page only</label>
                    <label class="acheck"><input type="radio" name="placement" value="both" @checked(old('placement') === 'both')> {{ $district->name }} page + Homepage</label>
                </fieldset>
                <x-admin.input name="sort_order" label="Display order" type="number" value="0" min="0" />
                <button type="submit" class="abtn abtn--primary">Upload photos</button>
            </form>
        </aside>
    </div>
@endsection
