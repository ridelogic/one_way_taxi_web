@props(['name', 'label', 'value' => null, 'help' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = 'f_'.str_replace('.', '_', $key);
    $currentId = old($key, $value);
    $media = $currentId ? \App\Models\MediaAsset::find($currentId) : null;
@endphp
<div @class(['afield', 'media-field', 'has-error' => $errors->has($key)]) data-media-field>
    <label for="{{ $id }}">{{ $label }}</label>
    <input type="hidden" id="{{ $id }}" name="{{ $name }}" value="{{ $media?->id }}" data-media-input>
    <div class="media-field__box">
        <div class="media-field__preview" data-media-preview>
            @if ($media)
                <img src="{{ $media->safeUrl() }}" alt="" style="object-fit:{{ $media->object_fit }};object-position:{{ $media->objectPosition() }}">
            @else
                <span class="media-field__empty"><x-icon name="image" :size="28" /> No image selected</span>
            @endif
        </div>
        <div class="media-field__actions">
            <button type="button" class="abtn abtn--primary abtn--sm" data-media-choose><x-icon name="image" :size="16" /> Choose image</button>
            <button type="button" class="abtn abtn--sm" data-media-clear @if (! $media) hidden @endif>Remove</button>
            <a class="abtn abtn--sm abtn--ghost" data-media-edit href="{{ $media ? route('admin.media.edit', $media) : '#' }}" target="_blank" @if (! $media) hidden @endif><x-icon name="sliders" :size="14" /> Crop &amp; position</a>
        </div>
    </div>
    @if ($help)<p class="afield__help">{{ $help }}</p>@endif
    @error($key)<p class="afield__error">{{ $message }}</p>@enderror
</div>
