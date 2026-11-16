@extends('admin.layout')

@section('title', 'Pages')

@section('content')
    <div class="page-head">
        <form method="GET" class="filters" style="margin:0">
            <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $search }}" placeholder="Title or URL"></div>
            <div class="afield">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    @foreach (\App\Models\Page::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="abtn" type="submit">Filter</button>
        </form>
        <a class="abtn abtn--primary" href="{{ route('admin.pages.create') }}"><x-icon name="plus" :size="16" /> New page</a>
    </div>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th>Title</th><th>URL</th><th>Template</th><th>Status</th><th>In menu</th><th>Updated</th><th></th></tr></thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td class="strong">{{ $page->title }} @if ($page->is_system)<span class="abadge abadge--slate">System</span>@endif</td>
                        <td><a href="{{ $page->url() }}" target="_blank" rel="noopener">/{{ $page->slug === 'home' ? '' : $page->slug }}</a></td>
                        <td class="muted">{{ $page->template === 'home' ? 'Homepage' : (\App\Models\Page::TEMPLATES[$page->template] ?? $page->template) }}</td>
                        <td><x-admin.badge :on="$page->isPublished()" on-label="Published" off-label="Draft" /></td>
                        <td>{{ $page->show_in_nav ? 'Yes' : '—' }}</td>
                        <td class="muted">{{ $page->updated_at->format('d M Y') }}</td>
                        <td class="actions">
                            @if ($page->slug === 'home')
                                <a class="abtn abtn--sm" href="{{ route('admin.homepage.index') }}">Edit sections</a>
                            @else
                                <a class="abtn abtn--sm" href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener">Preview</a>
                                <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.pages.edit', $page) }}">Edit</a>
                                @unless ($page->is_system)
                                    <x-admin.delete :action="route('admin.pages.destroy', $page)" />
                                @endunless
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No pages found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $pages->links('admin.partials.pagination') }}
@endsection
