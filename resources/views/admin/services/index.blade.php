@extends('admin.layout')

@section('title', 'Services')

@section('content')
    <div class="page-head">
        <p>Services appear on the Services page, the header dropdown, and (when ticked) on the homepage.</p>
        <a class="abtn abtn--primary" href="{{ route('admin.services.create') }}"><x-icon name="plus" :size="16" /> Add service</a>
    </div>
    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th></th><th>Service</th><th>URL</th><th>Homepage</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td><span style="color:var(--a-accent)"><x-icon :name="$service->icon" :size="22" /></span></td>
                        <td class="strong">{{ $service->title }}<br><small class="muted">{{ \Illuminate\Support\Str::limit($service->short_description, 70) }}</small></td>
                        <td><a href="{{ route('services.show', $service->slug) }}" target="_blank" rel="noopener">/services/{{ $service->slug }}</a></td>
                        <td>{{ $service->show_on_home ? 'Yes' : '—' }}</td>
                        <td>{{ $service->sort_order }}</td>
                        <td><x-admin.badge :on="$service->is_active" /></td>
                        <td class="actions">
                            <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.services.edit', $service) }}">Edit</a>
                            <x-admin.delete :action="route('admin.services.destroy', $service)" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No services yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
