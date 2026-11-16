@props(['name', 'label', 'for' => null, 'icon' => null, 'required' => false, 'hint' => null, 'error' => null])
@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $message = $error ?? $errors->first($errorKey);
@endphp
<div {{ $attributes->class(['field relative', 'has-error' => $message]) }}>
    <label for="{{ $for ?? $name }}" class="form-label">{{ $label }}@if ($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    @if ($icon)
        <span class="form-icon" aria-hidden="true"><x-icon :name="$icon" :size="18" /></span>
    @endif
    {{ $slot }}
    @if ($hint)
        <p class="mt-1.5 text-xs text-ink-soft">{{ $hint }}</p>
    @endif
    @if ($message)
        <p class="form-error" role="alert">{{ $message }}</p>
    @endif
</div>
