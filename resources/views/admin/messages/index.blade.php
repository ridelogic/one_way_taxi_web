@extends('admin.layout')

@section('title', 'Contact messages')

@section('content')
    <form method="GET" class="filters">
        <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="Name, phone, email or text"></div>
        <div class="afield">
            <label for="filter">Show</label>
            <select id="filter" name="filter">
                <option value="">All</option>
                <option value="unread" @selected(request('filter') === 'unread')>Unread</option>
            </select>
        </div>
        <button class="abtn" type="submit">Filter</button>
    </form>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th>From</th><th>Subject</th><th>Received</th><th></th><th></th></tr></thead>
            <tbody>
                @forelse ($messages as $message)
                    <tr>
                        <td @class(['strong' => ! $message->read_at])>{{ $message->name }}<br><small class="muted">{{ $message->phone }}</small></td>
                        <td>{{ $message->subject ?: \Illuminate\Support\Str::limit($message->message, 60) }}</td>
                        <td class="muted">{{ $message->created_at->format('d M Y, H:i') }}</td>
                        <td>@unless ($message->read_at)<span class="abadge abadge--amber">New</span>@endunless</td>
                        <td class="actions"><a class="abtn abtn--sm abtn--primary" href="{{ route('admin.messages.show', $message) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">{{ collect(request()->except('page'))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty() ? 'No messages match this filter.' : 'No messages yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $messages->links('admin.partials.pagination') }}
@endsection
