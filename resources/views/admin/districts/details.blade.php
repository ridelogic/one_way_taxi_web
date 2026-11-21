@extends('admin.layout')

@section('title', $district->exists ? $district->name.' · District Details' : 'Add a district')

@section('content')
    @if ($district->exists)
        @include('admin.districts._header', ['tab' => 'details'])
    @else
        <a href="{{ route('admin.districts.index') }}">← All districts</a>
    @endif

    <form method="POST" action="{{ $district->exists ? route('admin.districts.update', $district) : route('admin.districts.store') }}" style="margin-top:12px">
        @csrf
        @if ($district->exists) @method('PUT') @endif
        <div class="layout-2">
            <div>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="map-pin" :size="18" /> District Details</h2>
                    <div class="aform-grid">
                        <x-admin.input name="name" label="District name" :value="$district->name" required />
                        <x-admin.input name="slug" label="Web address (URL slug)" :value="$district->slug" data-slug-from="f_name" help="The page address: /tamil-nadu/slug. Changing it changes the page link." />
                    </div>
                    <x-admin.input name="heading" label="Page title (main heading)" :value="$district->heading" help="Shown at the top of the page. Leave blank for “Reliable Taxi Services in {district}”." />
                    <x-admin.input name="summary" label="Short description" :value="$district->summary" help="One line shown on the district card (max 200 characters)." />
                    <x-admin.textarea name="intro" label="Banner text" :value="$district->intro" rows="2" help="Short text shown on the banner under the page title." />
                    <x-admin.rich name="content" label="Main description" :value="$district->content" help="The main article about taxi travel in this district. Write it specifically for this district." />
                </section>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="building" :size="18" /> Extra information</h2>
                    <div class="aform-grid">
                        <x-admin.input name="headquarters" label="Headquarters town" :value="$district->headquarters" />
                        <x-admin.input name="region" label="Region" :value="$district->region" help="e.g. Western Tamil Nadu" />
                        <x-admin.input name="nearest_airport" label="Nearest airport" :value="$district->nearest_airport" />
                        <x-admin.input name="state" label="State" :value="$district->state" />
                    </div>
                </section>
            </div>
            <aside>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="eye" :size="18" /> Publishing</h2>
                    <x-admin.checkbox name="is_published" label="Active — show this page on the website" :checked="$district->is_published" />
                    <x-admin.checkbox name="content_reviewed" label="Content reviewed and confirmed" :checked="$district->content_reviewed" />
                    <p class="acard__help">Seeded district text is a draft. Tick this once someone has checked it.</p>
                    <x-admin.input name="sort_order" label="Display order" type="number" :value="$district->sort_order" min="0" help="0 keeps alphabetical order." />
                </section>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="image" :size="18" /> Images</h2>
                    <x-admin.media name="hero_media_id" label="District banner image" :value="$district->hero_media_id" help="The large photo at the top of the page." />
                    <x-admin.media name="card_media_id" label="Featured image" :value="$district->card_media_id" help="Shown on the district card on the homepage. Defaults to the banner image." />
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $district->exists ? 'Save district details' : 'Add district' }}</button>
        </div>
    </form>
@endsection
