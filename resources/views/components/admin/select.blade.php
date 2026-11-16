@props(['name', 'label', 'options' => [], 'value' => null, 'help' => null, 'required' => false, 'placeholder' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = 'f_'.str_replace('.', '_', $key);
    $current = (string) old($key, $value);
@endphp
<div @class(['afield', 'has-error' => $errors->has($key)])>
    <label for="{{ $id }}">{{ $label }}@if ($required)<span class="req"> *</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($help)<p class="afield__help">{{ $help }}</p>@endif
    @error($key)<p class="afield__error">{{ $message }}</p>@enderror
</div>
