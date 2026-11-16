@extends('admin.layout')

@section('title', $slide->exists ? 'Edit slide' : 'Add slide')

@section('content')
    <a href="{{ route('admin.slides.index') }}">← Hero slides</a>
    <form method="POST" action="{{ $slide->exists ? route('admin.slides.update', $slide) : route('admin.slides.store') }}" style="margin-top:12px">
        @csrf
        @if ($slide->exists) @method('PUT') @endif
        <div class="layout-2">
            <div>
                <section class="acard">
                    <h3 class="acard__title">Text</h3>
                    <x-admin.input name="eyebrow" label="Small label above the headline" :value="$slide->eyebrow" maxlength="120" />
                    <div class="aform-grid">
                        <x-admin.input name="heading" label="Headline" :value="$slide->heading" required maxlength="160" help="e.g. Your Journey." />
                        <x-admin.input name="highlight" label="Highlighted line" :value="$slide->highlight" maxlength="120" help="Second heading line. Wrap words in *asterisks* to colour only them in orange, e.g. *One-Way* Ride — without asterisks the whole line is orange." />
                    </div>
                    <x-admin.input name="subheading" label="Subheading (e.g. One Way Taxi Across India)" :value="$slide->subheading" maxlength="160" />
                    <x-admin.textarea name="subtitle" label="Supporting text" :value="$slide->subtitle" rows="3" maxlength="400" />
                </section>
                <section class="acard">
                    <h3 class="acard__title">Buttons</h3>
                    <div class="aform-grid">
                        <x-admin.input name="primary_label" label="Primary button label (gold)" :value="$slide->primary_label" maxlength="60" />
                        <x-admin.input name="primary_url" label="Primary button link" :value="$slide->primary_url" placeholder="#book-ride or /book" />
                        <x-admin.input name="secondary_label" label="Secondary button label" :value="$slide->secondary_label" maxlength="60" />
                        <x-admin.input name="secondary_url" label="Secondary button link" :value="$slide->secondary_url" placeholder="/routes" />
                    </div>
                    <p class="afield__help">Use <code>#book-ride</code> to scroll to the homepage booking form.</p>
                </section>
            </div>
            <aside>
                <section class="acard">
                    <h3 class="acard__title">Hero image</h3>
                    <x-admin.media name="media_id" label="Hero scene photo (right side, e.g. an airport or highway at sunset)" :value="$slide->media_id" help="Shown to the right of the navy panel. Use “Crop & position” to choose which part stays in view." />
                    <x-admin.media name="foreground_media_id" label="Vehicle cut-out (not shown on the homepage hero)" :value="$slide->foreground_media_id" help="The homepage hero uses a car-free scene photo, so any image saved here is not displayed there." />
                </section>
                <section class="acard">
                    <h3 class="acard__title">Display</h3>
                    <x-admin.checkbox name="is_active" label="Visible on the homepage" :checked="$slide->is_active" />
                    <x-admin.select name="text_align" label="Text alignment" :options="['left' => 'Left', 'center' => 'Centred']" :value="$slide->text_align" />
                    <x-admin.input name="overlay_strength" label="Image darkening (0–90%, used by darker layouts)" type="number" min="0" max="90" :value="$slide->overlay_strength" />
                    <x-admin.input name="sort_order" label="Order" type="number" min="0" :value="$slide->sort_order" />
                </section>
            </aside>
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $slide->exists ? 'Save slide' : 'Add slide' }}</button>
            <a href="{{ route('admin.slides.index') }}" class="abtn">Cancel</a>
            <span class="spacer"></span>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="abtn abtn--ghost"><x-icon name="eye" :size="16" /> View homepage</a>
        </div>
    </form>
@endsection
