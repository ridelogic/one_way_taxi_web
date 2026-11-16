@props(['name', 'label', 'value' => [], 'help' => 'One item per line.'])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $text = old($key, is_array($value) ? implode("\n", $value) : (string) $value);
@endphp
<x-admin.textarea :name="$name" :label="$label" :value="$text" :help="$help" rows="5" />
