@extends('admin.layout')

@section('title', 'Photos')

@section('content')
    <div class="page-head">
        <form method="GET" class="filters" style="margin:0">
            <div class="afield">
                <label for="placement">Show</label>
                <select id="placement" name="placement">
                    <option value="home" @selected($placement === 'home')>Homepage photos</option>
                    <option value="all" @selected($placement === 'all')>All photos (homepage + districts)</option>
                    <option value="inactive" @selected($placement === 'inactive')>Hidden photos</option>
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
    </div>
    <p class="acard__help" style="margin:-6px 0 16px">Homepage photos appear in the homepage Photo Gallery section. District photos can also be managed inside each district (District Management → Photos). One photo can be shown on the homepage and on several districts.</p>

    <div class="layout-2">
        <section class="acard">
            @if ($photos->isEmpty())
                <p class="empty">No photos match. Upload photos using the form.</p>
            @else
                <ul class="photo-grid">
                    @foreach ($photos as $photo)
                        <li @class(['photo-grid__item', 'is-hidden' => ! $photo->is_active])>
                            @if ($photo->media)<img src="{{ $photo->media->safeUrl() }}" alt="" loading="lazy">@endif
                            <div class="photo-grid__body">
                                <strong>{{ $photo->title ?: 'Untitled photo' }}</strong>
                                <small class="muted">
                                    @if ($photo->show_on_home)Homepage @endif
                                    @if ($photo->show_on_districts && $photo->districts->isNotEmpty()){{ $photo->show_on_home ? '· ' : '' }}{{ $photo->districts->take(2)->pluck('name')->join(', ') }}{{ $photo->districts->count() > 2 ? ' +'.($photo->districts->count() - 2) : '' }}@endif
                                    · Order {{ $photo->sort_order }}
                                </small>
                                <div class="photo-grid__actions">
                                    <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.photos.edit', $photo) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.photos.toggle', $photo) }}" class="inline-form">@csrf @method('PATCH')<button class="abtn abtn--sm" type="submit">{{ $photo->is_active ? 'Hide' : 'Show' }}</button></form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                {{ $photos->links('admin.partials.pagination') }}
            @endif
        </section>

        <aside>
            <form method="POST" action="{{ route('admin.photos.store') }}" enctype="multipart/form-data" class="acard">
                @csrf
                <h2 class="acard__title"><x-icon name="upload" :size="18" /> Upload homepage photos</h2>
                <input type="hidden" name="return_to" value="{{ route('admin.photos.index') }}">
                <input type="hidden" name="placement" value="home">
                <input type="hidden" name="is_active" value="1">
                <div @class(['afield', 'has-error' => $errors->has('files') || $errors->has('files.*')])>
                    <label for="f_files">Photos <span class="req">*</span></label>
                    <input id="f_files" type="file" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                    <p class="afield__help">Select one or more photos (up to 20 at a time, 5 MB each).</p>
                </div>
                <x-admin.input name="title" label="Photo title" help="Used for every photo in this upload — change each one afterwards if needed." />
                <x-admin.textarea name="description" label="Photo description" rows="2" />
                <x-admin.input name="sort_order" label="Display order" type="number" value="0" min="0" />
                <button type="submit" class="abtn abtn--primary">Upload photos</button>
                <p class="acard__help" style="margin-top:10px">To also show a photo on district pages, open it with Edit and choose the districts.</p>
            </form>
        </aside>
    </div>
@endsection
