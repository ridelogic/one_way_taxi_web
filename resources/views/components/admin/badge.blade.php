@props(['on', 'onLabel' => 'Active', 'offLabel' => 'Hidden'])
<span class="abadge abadge--{{ $on ? 'green' : 'slate' }}">{{ $on ? $onLabel : $offLabel }}</span>
