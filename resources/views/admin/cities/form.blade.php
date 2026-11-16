@extends('admin.layout')

@section('title', $city->exists ? 'Edit city' : 'Add city')

@section('content')
    <a href="{{ route('admin.cities.index') }}">← All cities</a>
    <form method="POST" action="{{ $city->exists ? route('admin.cities.update', $city) : route('admin.cities.store') }}" style="margin-top:12px">
        @csrf
        @if ($city->exists) @method('PUT') @endif
        <div class="layout-2">
            <section class="acard">
                <div class="aform-grid">
                    <x-admin.input name="name" label="City name" :value="$city->name" required />
                    <x-admin.input name="state" label="State" :value="$city->state" />
                    <x-admin.input name="slug" label="Slug" :value="$city->slug" data-slug-from="f_name" help="Leave blank to generate from the name." />
                    <x-admin.input name="sort_order" label="Order" type="number" :value="$city->sort_order" min="0" />
                </div>
                <x-admin.textarea name="description" label="Description" :value="$city->description" rows="3" />
            </section>
            <aside>
                <section class="acard">
                    <x-admin.checkbox name="is_active" label="Active (available in booking forms)" :checked="$city->is_active" />
                    <x-admin.checkbox name="is_popular_destination" label="Show in “Popular destinations”" :checked="$city->is_popular_destination" />
                    <x-admin.media name="media_id" label="Photo" :value="$city->media_id" help="Used for destination tiles and as the fallback route thumbnail." />
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $city->exists ? 'Save city' : 'Add city' }}</button>
            <a href="{{ route('admin.cities.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
@endsection
