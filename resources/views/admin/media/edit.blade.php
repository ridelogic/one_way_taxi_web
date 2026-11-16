@extends('admin.layout')

@section('title', 'Edit image')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('admin.media.index') }}">← Media library</a>
            <h2 style="margin:6px 0 0">{{ $media->title ?: $media->original_name }}</h2>
            <p>Used in {{ $usage }} place(s). Changes to crop, position and alt text apply everywhere this image is used.</p>
        </div>
        <div class="page-head__actions">
            <a class="abtn" href="{{ $media->safeUrl() }}" target="_blank" rel="noopener"><x-icon name="external-link" :size="16" /> Open file</a>
            <x-admin.delete :action="route('admin.media.destroy', $media)" label="Delete image" confirm="Delete this image permanently?" />
        </div>
    </div>

    @unless ($media->fileExists())
        <div class="aalert aalert--error">The image file is missing on disk. The website shows a placeholder instead. Upload a replacement below.</div>
    @endunless

    <div class="layout-2">
        <div>
            <section class="acard">
                <h3 class="acard__title">Crop &amp; position</h3>
                <p class="acard__help">Click or drag on the image to set the focal point — the part that always stays visible when the image is cropped to fit different shapes. Cropping is non-destructive: the original file is kept.</p>
                <div class="focal" data-focal>
                    <img src="{{ $media->safeUrl() }}" alt="{{ $media->alt_text }}" draggable="false">
                    <span class="focal__dot" data-focal-dot></span>
                </div>
                <p class="muted" style="font-size:.85rem">object-position: <code data-focal-output>{{ $media->objectPosition() }}</code></p>

                <h4 style="margin:22px 0 10px">Preview in common shapes</h4>
                <div class="preview-grid">
                    @foreach (['16 / 9' => 'Wide 16:9', '4 / 3' => 'Card 4:3', '1 / 1' => 'Square', '3 / 4' => 'Portrait', '21 / 9' => 'Banner'] as $ratio => $label)
                        <figure>
                            <div class="frame" style="aspect-ratio: {{ $ratio }}"><img src="{{ $media->safeUrl() }}" alt="" data-focal-preview style="object-fit:{{ $media->object_fit }};object-position:{{ $media->objectPosition() }}"></div>
                            <figcaption>{{ $label }}</figcaption>
                        </figure>
                    @endforeach
                </div>

                <h4 style="margin:22px 0 10px">Responsive preview</h4>
                <div class="device-previews">
                    <figure style="margin:0">
                        <div class="device" style="width:320px;height:180px"><img src="{{ $media->safeUrl() }}" alt="" data-focal-preview style="object-fit:{{ $media->object_fit }};object-position:{{ $media->objectPosition() }}"></div>
                        <figcaption class="muted" style="font-size:.75rem;text-align:center">Desktop</figcaption>
                    </figure>
                    <figure style="margin:0">
                        <div class="device" style="width:150px;height:200px"><img src="{{ $media->safeUrl() }}" alt="" data-focal-preview style="object-fit:{{ $media->object_fit }};object-position:{{ $media->objectPosition() }}"></div>
                        <figcaption class="muted" style="font-size:.75rem;text-align:center">Tablet</figcaption>
                    </figure>
                    <figure style="margin:0">
                        <div class="device" style="width:90px;height:180px"><img src="{{ $media->safeUrl() }}" alt="" data-focal-preview style="object-fit:{{ $media->object_fit }};object-position:{{ $media->objectPosition() }}"></div>
                        <figcaption class="muted" style="font-size:.75rem;text-align:center">Mobile</figcaption>
                    </figure>
                    <figure style="margin:0;flex:1;min-width:200px">
                        <div class="frame" data-ratio-preview data-original="{{ $media->width && $media->height ? $media->width.' / '.$media->height : '16 / 9' }}" style="aspect-ratio: {{ $media->aspectRatioCss() ?? '16 / 9' }};border-radius:8px;overflow:hidden"><img src="{{ $media->safeUrl() }}" alt="" data-focal-preview style="width:100%;height:100%;object-fit:{{ $media->object_fit }};object-position:{{ $media->objectPosition() }}"></div>
                        <figcaption class="muted" style="font-size:.75rem;text-align:center">Default aspect ratio</figcaption>
                    </figure>
                </div>
            </section>
        </div>

        <aside>
            <form method="POST" action="{{ route('admin.media.update', $media) }}" class="acard">
                @csrf
                @method('PUT')
                <h3 class="acard__title">Details</h3>
                <x-admin.input name="title" label="Title" :value="$media->title" />
                <x-admin.textarea name="alt_text" label="Alt text" :value="$media->alt_text" rows="2" help="Describe the image for screen readers and search engines." />
                <x-admin.select name="aspect_ratio" label="Default aspect ratio" :options="\App\Models\MediaAsset::ASPECT_RATIOS" :value="$media->aspect_ratio" />
                <x-admin.select name="object_fit" label="Fit (object-fit)" :options="\App\Models\MediaAsset::OBJECT_FITS" :value="$media->object_fit" />
                <div class="aform-grid">
                    <x-admin.input name="focal_x" label="Focal X %" type="number" :value="$media->focal_x" min="0" max="100" />
                    <x-admin.input name="focal_y" label="Focal Y %" type="number" :value="$media->focal_y" min="0" max="100" />
                </div>
                <x-admin.textarea name="credit" label="Credit / licence" :value="$media->credit" rows="3" help="Shown on the Image Credits page when filled in." />
                <button type="submit" class="abtn abtn--primary">Save details</button>
            </form>

            <section class="acard">
                <h3 class="acard__title">File</h3>
                <dl class="meta-list">
                    <dt>Dimensions</dt><dd>{{ $media->width ? $media->width.' × '.$media->height.' px' : 'Unknown' }}</dd>
                    <dt>Size</dt><dd>{{ $media->humanSize() }}</dd>
                    <dt>Type</dt><dd>{{ $media->mime_type }}</dd>
                    <dt>Stored as</dt><dd>{{ $media->disk }}/{{ $media->path }}</dd>
                    <dt>URL</dt><dd><a href="{{ $media->url() }}" target="_blank" rel="noopener">{{ $media->url() }}</a></dd>
                </dl>
                <form method="POST" action="{{ route('admin.media.replace', $media) }}" enctype="multipart/form-data" style="margin-top:16px">
                    @csrf
                    <div class="afield">
                        <label for="replace-file">Replace file</label>
                        <input id="replace-file" type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif,image/x-icon" required>
                        <p class="afield__help">Keeps this image’s ID, so every page using it updates automatically.</p>
                    </div>
                    <button type="submit" class="abtn">Replace image</button>
                </form>
            </section>
        </aside>
    </div>
@endsection
