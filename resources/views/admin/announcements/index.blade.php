@extends('admin.layout')

@section('title', 'Promotional Announcements')

@section('content')
    @php($statusColours = ['Live' => 'green', 'Scheduled' => 'blue', 'Expired' => 'amber', 'Disabled' => 'slate'])
    <div class="page-head">
        <p>
            The scrolling offers banner below the header on the homepage and the location pages. An announcement shows
            only while it is <strong>Enabled</strong>, inside its start/end dates and on the pages chosen in
            <strong>Display locations</strong>; new announcements start disabled.
            @if ($liveCount > 0)
                <strong>{{ $liveCount }} {{ \Illuminate\Support\Str::plural('announcement', $liveCount) }} showing now.</strong>
            @else
                <strong>Nothing is showing — the banner is hidden on the homepage.</strong>
            @endif
        </p>
        <a class="abtn abtn--primary" href="{{ route('admin.announcements.create') }}"><x-icon name="plus" :size="16" /> Add announcement</a>
    </div>

    @if ($targetingPending ?? false)
        <p class="empty" style="border-color:#f59e0b">A database update for <strong>Display locations</strong> (homepage / location pages) is waiting to be applied. Until then existing announcements keep their homepage-only behaviour, and you can enable, disable, preview or delete them; adding and editing resume after the update.</p>
    @endif
    @if ($tableMissing ?? false)
        <p class="empty">Announcements are not set up on this database yet: the <code>promo_announcements</code> table has not been created (its migration is waiting to be run). The homepage banner stays hidden until then.</p>
    @elseif ($announcements->isEmpty())
        <p class="empty">No announcements yet. The homepage banner stays hidden until you add and enable one.</p>
    @else
        <section class="acard atable-wrap" style="padding:0">
            <table class="atable">
                <thead><tr><th>Order</th><th>Announcement</th><th>Shows on</th><th>Link</th><th>Shows from</th><th>Until</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($announcements as $a)
                        @php($status = $a->displayStatus())
                        <tr>
                            <td>{{ $a->sort_order }}</td>
                            <td><span class="strong">{{ $a->title }}</span><br><span class="muted">{{ \Illuminate\Support\Str::limit($a->message, 90) }}</span></td>
                            <td>
                                {{ $a->targetSummary() }}
                                @if ($a->location_scope === \App\Models\PromoAnnouncement::SCOPE_SELECTED && $a->districts->isNotEmpty())
                                    <br><span class="muted">{{ \Illuminate\Support\Str::limit($a->districts->pluck('name')->join(', '), 60) }}</span>
                                @endif
                            </td>
                            <td class="muted">{{ $a->link_url ?\Illuminate\Support\Str::limit($a->link_url, 32) : '—' }}</td>
                            <td class="muted">{{ $a->starts_at?->format('d M Y, h:i A') ?? 'Immediately' }}</td>
                            <td class="muted">{{ $a->ends_at?->format('d M Y, h:i A') ?? 'No end date' }}</td>
                            <td><span class="abadge abadge--{{ $statusColours[$status] }}">{{ $status }}</span></td>
                            <td class="actions">
                                <a class="abtn abtn--sm" href="{{ route('admin.announcements.preview', $a) }}" target="_blank" rel="noopener"><x-icon name="eye" :size="14" /> Preview</a>
                                <form method="POST" action="{{ route('admin.announcements.toggle', $a) }}" class="inline-form">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="abtn abtn--sm">{{ $a->is_enabled ? 'Disable' : 'Enable' }}</button>
                                </form>
                                <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.announcements.edit', $a) }}">Edit</a>
                                <x-admin.delete :action="route('admin.announcements.destroy', $a)" confirm="Delete this announcement? This cannot be undone." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
