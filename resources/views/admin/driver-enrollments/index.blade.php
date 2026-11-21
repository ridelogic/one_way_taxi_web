@extends('admin.layout')

@section('title', 'Driver enrollments')

@section('content')
    <p class="muted" style="margin-bottom:1rem">Drivers who enrolled on the website with their taxi. Each one is created in the fleet admin as an <strong>inactive</strong> driver — activate it there under Driver → Manage Drivers after checking the documents.</p>

    <nav class="tabs" aria-label="Status filter">
        <a href="{{ route('admin.driver-enrollments.index', request()->except(['status', 'page'])) }}" @class(['is-active' => ! $status])>All ({{ $counts->sum() }})</a>
        @foreach (['sent' => 'In fleet admin', 'failed' => 'Not sent', 'pending' => 'Sending'] as $key => $label)
            <a href="{{ route('admin.driver-enrollments.index', array_merge(request()->except('page'), ['status' => $key])) }}" @class(['is-active' => $status === $key])>{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
    </nav>

    <form method="GET" class="filters">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="Reference, name, phone, vehicle number…"></div>
        <button class="abtn" type="submit">Search</button>
    </form>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead>
                <tr><th>Reference</th><th>Driver</th><th>Vehicle</th><th>Licence</th><th>Received</th><th>Fleet admin</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($enrollments as $e)
                    <tr>
                        <td><span class="ref">{{ $e->reference }}</span></td>
                        <td class="strong">{{ $e->fullName() }}<br><small class="muted">+91 {{ $e->phone }}</small></td>
                        <td>{{ $e->model_name }}<br><small class="muted">{{ $e->taxi_no }} · {{ $e->manufacturing_year }}</small></td>
                        <td>{{ $e->licence_no }}<br><small class="muted">expires {{ $e->licence_expiry->format('d M Y') }}</small></td>
                        <td class="muted">{{ $e->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            @if ($e->status === 'sent')
                                <span class="abadge abadge--green">Driver #{{ $e->fleet_driver_id }}</span><br><small class="muted">Taxi #{{ $e->fleet_taxi_id ?? '—' }} · inactive until activated</small>
                            @elseif ($e->status === 'failed')
                                <span class="abadge abadge--red">Not sent</span><br><small class="muted">{{ \Illuminate\Support\Str::limit($e->sync_error, 120) }}</small>
                            @else
                                <span class="abadge">Sending</span>
                            @endif
                        </td>
                        <td class="actions">
                            @if ($e->status !== 'sent')
                                <form method="POST" action="{{ route('admin.driver-enrollments.resend', $e) }}">@csrf<button class="abtn abtn--sm abtn--primary" type="submit">Resend</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No driver enrollments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $enrollments->links('admin.partials.pagination') }}
@endsection
