@extends('layouts.app')

@section('content')
    {{-- Admin-only preview: the real site header (layout) + the ticker with only this announcement. --}}
    <x-offer-ticker :announcements="collect([$announcement])" />

    <div class="container-site" style="padding-block:2.5rem">
        <div style="max-width:720px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:1.25rem 1.5rem">
            <p style="font-weight:800;font-size:1.05rem;margin:0 0 .4rem">Preview — only administrators can see this page</p>
            <p style="margin:0 0 .4rem">This is how “{{ $announcement->title }}” scrolls below the header.</p>
            <p style="margin:0 0 .4rem">
                Display locations: <strong>{{ $announcement->targetSummary() }}</strong>
                @if ($announcement->location_scope === \App\Models\PromoAnnouncement::SCOPE_SELECTED && $announcement->districts->isNotEmpty())
                    — {{ $announcement->districts->pluck('name')->join(', ') }}
                @endif
            </p>
            <p style="margin:0 0 1rem">
                Current status: <strong>{{ $announcement->displayStatus() }}</strong> —
                @if ($announcement->isLive())
                    it is showing on those pages now.
                @else
                    it is <strong>not</strong> showing anywhere yet. Enable it (and check its dates) to publish it.
                @endif
            </p>
            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-outline">Back to editing</a>
        </div>
    </div>
@endsection
