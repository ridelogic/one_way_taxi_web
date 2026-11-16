@props(['name', 'label', 'checked' => false, 'help' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = 'f_'.str_replace('.', '_', $key);
    $isChecked = old($key) !== null ? (bool) old($key) : (bool) $checked;
@endphp
<div class="afield afield--check">
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="acheck" for="{{ $id }}">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked($isChecked) {{ $attributes }}>
        <span>{{ $label }}</span>
    </label>
    @if ($help)<p class="afield__help">{{ $help }}</p>@endif
    @error($key)<p class="afield__error">{{ $message }}</p>@enderror
</div>
