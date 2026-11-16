@props(['status'])
@php($status = $status instanceof \App\Enums\BookingStatus ? $status : \App\Enums\BookingStatus::tryFrom((string) $status))
<span class="abadge abadge--{{ $status?->tone() ?? 'slate' }}">{{ $status?->label() ?? '—' }}</span>
