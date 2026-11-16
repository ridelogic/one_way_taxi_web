@props(['show' => true, 'label' => 'Sample'])
@if ($show && settings()->bool('demo_mode'))
    <span {{ $attributes->merge(['class' => 'badge-sample']) }} title="Demonstration figure — to be verified by the business owner">{{ $label }}</span>
@endif
