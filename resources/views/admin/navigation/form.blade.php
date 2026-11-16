@extends('admin.layout')

@section('title', $item->exists ? 'Edit menu link' : 'Add menu link')

@section('content')
    <a href="{{ route('admin.navigation.index') }}">← Navigation</a>
    <form method="POST" action="{{ $item->exists ? route('admin.navigation.update', $item) : route('admin.navigation.store') }}" class="acard" style="margin-top:12px;max-width:760px">
        @csrf
        @if ($item->exists) @method('PUT') @endif
        <x-admin.select name="menu" label="Menu" :options="\App\Models\NavigationItem::MENUS" :value="$item->menu" required />
        <x-admin.input name="label" label="Label" :value="$item->label" required maxlength="80" />
        <x-admin.input name="url" label="URL" :value="$item->url" required help="A site path such as /routes or /services/airport-transfers, or a full https:// link." />
        <x-admin.input name="sort_order" label="Order" type="number" min="0" :value="$item->sort_order" />
        <x-admin.checkbox name="is_active" label="Active" :checked="$item->is_active" />
        <x-admin.checkbox name="open_in_new_tab" label="Open in a new tab" :checked="$item->open_in_new_tab" />
        <button type="submit" class="abtn abtn--primary">Save link</button>
        <a href="{{ route('admin.navigation.index') }}" class="abtn">Cancel</a>
    </form>
@endsection
