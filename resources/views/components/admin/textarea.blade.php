@props(['name', 'label', 'value' => null, 'help' => null, 'required' => false, 'rows' => 4])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = 'f_'.str_replace('.', '_', $key);
@endphp
<div @class(['afield', 'has-error' => $errors->has($key)])>
    <label for="{{ $id }}">{{ $label }}@if ($required)<span class="req"> *</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) {{ $attributes }}>{{ old($key, $value) }}</textarea>
    @if ($help)<p class="afield__help">{{ $help }}</p>@endif
    @error($key)<p class="afield__error">{{ $message }}</p>@enderror
</div>
