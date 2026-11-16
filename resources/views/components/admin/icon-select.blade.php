@props(['name', 'label' => 'Icon', 'value' => null])
@php
    $icons = array_keys(config('icons'));
    $options = array_combine($icons, array_map(fn ($i) => \Illuminate\Support\Str::headline($i), $icons));
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $selected = (string) old($key, $value ?: 'check');
@endphp
<div class="icon-select" data-icon-select>
    <span class="icon-select__preview" aria-hidden="true">
        @foreach ($icons as $icon)
            <span data-icon="{{ $icon }}" @if ($selected !== $icon) hidden @endif><x-icon :name="$icon" :size="22" /></span>
        @endforeach
    </span>
    <x-admin.select :name="$name" :label="$label" :options="$options" :value="$selected" />
</div>
