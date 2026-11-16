@extends('admin.layout')

@section('title', 'Navigation')

@section('content')
    <p class="muted">The header “Services” link automatically shows a dropdown of active services.</p>
    @foreach (\App\Models\NavigationItem::MENUS as $menu => $label)
        <section class="acard atable-wrap" style="padding:0">
            <div class="page-head" style="padding:16px 16px 0;margin:0">
                <h2 class="acard__title" style="margin:0">{{ $label }}</h2>
                <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.navigation.create', ['menu' => $menu]) }}"><x-icon name="plus" :size="14" /> Add link</a>
            </div>
            <table class="atable">
                <thead><tr><th>Label</th><th>URL</th><th>Order</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($menus->get($menu, collect()) as $item)
                        <tr>
                            <td class="strong">{{ $item->label }}</td>
                            <td><a href="{{ $item->href() }}" target="_blank" rel="noopener">{{ $item->url }}</a></td>
                            <td>{{ $item->sort_order }}</td>
                            <td><x-admin.badge :on="$item->is_active" /></td>
                            <td class="actions">
                                <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.navigation.edit', $item) }}">Edit</a>
                                <x-admin.delete :action="route('admin.navigation.destroy', $item)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No links in this menu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endforeach
@endsection
