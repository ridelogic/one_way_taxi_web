@extends('admin.layout')

@section('title', $vehicle->exists ? 'Edit vehicle' : 'Add vehicle')

@section('content')
    <a href="{{ route('admin.vehicles.index') }}">← Vehicle Types</a>
    <form method="POST" action="{{ $vehicle->exists ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}" style="margin-top:12px">
        @csrf
        @if ($vehicle->exists) @method('PUT') @endif
        <div class="layout-2">
            <div>
                <section class="acard">
                    <div class="aform-grid">
                        <x-admin.input name="name" label="Vehicle name" :value="$vehicle->name" required />
                        <x-admin.input name="category" label="Category" :value="$vehicle->category" placeholder="Sedan, SUV, MUV…" />
                        <x-admin.input name="slug" label="Slug" :value="$vehicle->slug" data-slug-from="f_name" help="Leave blank to generate from the name." />
                        <x-admin.input name="example_models" label="Example models" :value="$vehicle->example_models" />
                        <x-admin.input name="passenger_capacity" label="Passengers" type="number" min="1" max="60" :value="$vehicle->passenger_capacity" required />
                        <x-admin.input name="luggage_capacity" label="Luggage (bags)" type="number" min="0" max="60" :value="$vehicle->luggage_capacity" required />
                    </div>
                    <x-admin.input name="short_description" label="Short description" :value="$vehicle->short_description" maxlength="255" />
                    <x-admin.textarea name="description" label="Description" :value="$vehicle->description" rows="5" />
                    <x-admin.lines name="features" label="Features" :value="$vehicle->features ?? []" />
                </section>
                <section class="acard">
                    <h3 class="acard__title">SEO</h3>
                    <x-admin.input name="seo_title" label="Meta title" :value="$vehicle->seo_title" maxlength="120" />
                    <x-admin.textarea name="seo_description" label="Meta description" :value="$vehicle->seo_description" rows="2" maxlength="300" />
                </section>
            </div>
            <aside>
                <section class="acard">
                    <x-admin.checkbox name="is_active" label="Active (bookable and listed)" :checked="$vehicle->is_active" />
                    <x-admin.input name="sort_order" label="Display order" type="number" min="0" :value="$vehicle->sort_order" />
                    <x-admin.media name="media_id" label="Photo" :value="$vehicle->media_id" />
                </section>
                @if ($vehicle->exists)
                    <a class="abtn" style="width:100%" href="{{ route('admin.fares.edit', $vehicle) }}"><x-icon name="rupee" :size="16" /> Edit fare rule</a>
                @endif
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $vehicle->exists ? 'Save vehicle' : 'Add vehicle' }}</button>
            <a href="{{ route('admin.vehicles.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
@endsection
