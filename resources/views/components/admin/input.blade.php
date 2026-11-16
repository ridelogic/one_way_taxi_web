@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = 'f_'.str_replace('.', '_', $key);
@endphp
<div @class(['afield', 'has-error' => $errors->has($key)])>
    <label for="{{ $id }}">{{ $label }}@if ($required)<span class="req"> *</span>@endif</label>
    <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($key, $value) }}" @required($required) {{ $attributes }}>
    @if ($help)<p class="afield__help">{{ $help }}</p>@endif
    @error($key)<p class="afield__error">{{ $message }}</p>@enderror
</div>
