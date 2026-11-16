@extends('admin.layout')

@section('title', $title)

@php($routeBase = $type === 'corporate' ? 'admin.corporate-enquiries' : 'admin.partner-enquiries')

@section('content')
    <nav class="tabs" aria-label="Status filter">
        <a href="{{ route($routeBase.'.index', request()->except(['status', 'page'])) }}" @class(['is-active' => ! $status])>All ({{ $counts->sum() }})</a>
        @foreach (\App\Enums\EnquiryStatus::cases() as $case)
            <a href="{{ route($routeBase.'.index', array_merge(request()->except('page'), ['status' => $case->value])) }}" @class(['is-active' => $status === $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </nav>

    <form method="GET" class="filters">
        <input type="hidden" name="status" value="{{ $status?->value }}">
        <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="Reference, name, phone…"></div>
        <button class="abtn" type="submit">Search</button>
    </form>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead>
                <tr>
                    <th>Reference</th>
                    @if ($type === 'corporate')<th>Company</th><th>Contact</th><th>Requirement</th>@else<th>Name</th><th>City</th><th>Type</th>@endif
                    <th>Received</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enquiries as $e)
                    <tr>
                        <td><span class="ref">{{ $e->reference }}</span></td>
                        @if ($type === 'corporate')
                            <td class="strong">{{ $e->company_name }}</td>
                            <td>{{ $e->contact_name }}<br><small class="muted">{{ $e->phone }}</small></td>
                            <td>{{ $e->requirementLabel() }}</td>
                        @else
                            <td class="strong">{{ $e->name }}<br><small class="muted">{{ $e->phone }}</small></td>
                            <td>{{ $e->city }}</td>
                            <td>{{ $e->partnerTypeLabel() }}</td>
                        @endif
                        <td class="muted">{{ $e->created_at->format('d M Y, H:i') }}</td>
                        <td><span class="abadge abadge--{{ $e->status->tone() }}">{{ $e->status->label() }}</span></td>
                        <td class="actions"><a class="abtn abtn--sm abtn--primary" href="{{ route($routeBase.'.show', $e) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">{{ collect(request()->except('page'))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty() ? 'No enquiries match these filters.' : 'No enquiries yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $enquiries->links('admin.partials.pagination') }}
@endsection
