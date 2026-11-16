@extends('admin.layout')

@section('title', $service->exists ? 'Edit service' : 'Add service')

@section('content')
    <a href="{{ route('admin.services.index') }}">← All services</a>
    <form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" style="margin-top:12px">
        @csrf
        @if ($service->exists) @method('PUT') @endif
        <div class="layout-2">
            <div>
                <section class="acard">
                    <div class="aform-grid">
                        <x-admin.input name="title" label="Title" :value="$service->title" required />
                        <x-admin.input name="slug" label="URL slug" :value="$service->slug" data-slug-from="f_title" help="Page lives at /services/slug." />
                    </div>
                    <x-admin.input name="short_description" label="Short description (cards)" :value="$service->short_description" maxlength="255" />
                    <x-admin.lines name="highlights" label="Highlights" :value="$service->highlights ?? []" />
                    <x-admin.rich name="body" label="Page content" :value="$service->body" />
                </section>
                <section class="acard">
                    <h3 class="acard__title">SEO</h3>
                    <x-admin.input name="seo_title" label="Meta title" :value="$service->seo_title" maxlength="120" />
                    <x-admin.textarea name="seo_description" label="Meta description" :value="$service->seo_description" rows="2" maxlength="300" />
                </section>
            </div>
            <aside>
                <section class="acard">
                    <x-admin.checkbox name="is_active" label="Active" :checked="$service->is_active" />
                    <x-admin.checkbox name="show_on_home" label="Show on homepage" :checked="$service->show_on_home" />
                    <x-admin.input name="sort_order" label="Order" type="number" min="0" :value="$service->sort_order" />
                    <x-admin.icon-select name="icon" label="Icon" :value="$service->icon" />
                    <x-admin.media name="media_id" label="Banner image" :value="$service->media_id" />
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $service->exists ? 'Save service' : 'Add service' }}</button>
            <a href="{{ route('admin.services.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
@endsection
