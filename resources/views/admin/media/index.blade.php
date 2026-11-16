@extends('admin.layout')

@section('title', 'Media library')

@section('content')
    <section class="acard">
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="dropzone" data-dropzone>
            @csrf
            <x-icon name="upload" :size="28" />
            <div style="flex:1;min-width:220px">
                <strong>Upload images</strong>
                <p class="muted" style="margin:2px 0 0;font-size:.85rem">Drag &amp; drop or choose files. JPG, PNG, WebP, GIF or ICO · max 5 MB each · up to 20 at once. Files get unique names automatically.</p>
            </div>
            <input type="file" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif,image/x-icon" multiple required aria-label="Images to upload">
            <input type="text" name="alt_text" placeholder="Alt text (optional)" maxlength="255" class="ainput" style="max-width:260px" aria-label="Alt text for the uploaded images">
            <button type="submit" class="abtn abtn--primary">Upload</button>
        </form>
    </section>

    <form method="GET" class="filters">
        <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="Title, alt text or file name"></div>
        <button class="abtn" type="submit">Search</button>
        @if ($search)<a class="abtn abtn--ghost" href="{{ route('admin.media.index') }}">Clear</a>@endif
    </form>

    <div class="media-grid">
        @forelse ($media as $item)
            <a class="media-tile" href="{{ route('admin.media.edit', $item) }}">
                <div class="media-tile__img"><img src="{{ $item->safeUrl() }}" alt="{{ $item->alt_text }}" loading="lazy" style="object-position:{{ $item->objectPosition() }}"></div>
                <div class="media-tile__body">
                    <div class="media-tile__title">{{ $item->title ?: $item->original_name }}</div>
                    <span class="muted">{{ $item->width }}×{{ $item->height }} · {{ $item->humanSize() }}</span>
                    @if (! $item->alt_text)<br><span class="abadge abadge--amber">No alt text</span>@endif
                    @if ($item->disk === 'demo')<br><span class="abadge abadge--slate">Bundled demo</span>@endif
                </div>
            </a>
        @empty
            <p class="empty">{{ collect(request()->except('page'))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty() ? 'No images match your search.' : 'No images yet.' }}</p>
        @endforelse
    </div>

    {{ $media->links('admin.partials.pagination') }}
@endsection
