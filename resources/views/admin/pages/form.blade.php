@extends('admin.layout')

@section('title', $page->exists ? 'Edit page' : 'New page')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('admin.pages.index') }}">← All pages</a>
            <h2 style="margin:6px 0 0">{{ $page->exists ? $page->title : 'New page' }}</h2>
        </div>
        @if ($page->exists)
            <div class="page-head__actions">
                <a class="abtn" href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener"><x-icon name="eye" :size="16" /> Preview</a>
                @if ($page->isPublished())
                    <a class="abtn" href="{{ $page->url() }}" target="_blank" rel="noopener"><x-icon name="external-link" :size="16" /> View live</a>
                @endif
            </div>
        @endif
    </div>

    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        <div class="layout-2">
            <div>
                <section class="acard">
                    <x-admin.input name="title" label="Page title" :value="$page->title" required />
                    <x-admin.input name="slug" label="URL slug" :value="$page->slug" data-slug-from="f_title" help="The page lives at {{ url('/') }}/your-slug. Leave blank to generate it from the title." />
                    <x-admin.input name="heading" label="Banner heading" :value="$page->heading" help="Defaults to the page title." />
                    <x-admin.textarea name="subheading" label="Banner subheading" :value="$page->subheading" rows="2" />
                    <x-admin.rich name="body" label="Content" :value="$page->body" />
                </section>

                <section class="acard">
                    <h3 class="acard__title">SEO</h3>
                    <x-admin.input name="seo_title" label="Meta title" :value="$page->seo_title" maxlength="120" help="Defaults to the page title." />
                    <x-admin.textarea name="seo_description" label="Meta description" :value="$page->seo_description" rows="3" maxlength="300" />
                </section>
            </div>

            <aside>
                <section class="acard">
                    <h3 class="acard__title">Publishing</h3>
                    <x-admin.select name="status" label="Status" :options="\App\Models\Page::STATUSES" :value="$page->status" />
                    <x-admin.select name="template" label="Template" :options="\App\Models\Page::TEMPLATES" :value="$page->template" />
                    <x-admin.checkbox name="show_in_nav" label="Show in the header menu" :checked="$page->show_in_nav" />
                    <x-admin.input name="nav_order" label="Menu position" type="number" :value="$page->nav_order" min="0" max="999" />
                    @if ($page->is_system)
                        <p class="afield__help">This is a system page. It can be edited and unpublished but not deleted.</p>
                    @endif
                </section>
                <section class="acard">
                    <h3 class="acard__title">Banner image</h3>
                    <x-admin.media name="hero_media_id" label="Image" :value="$page->hero_media_id" />
                </section>
            </aside>
        </div>

        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">{{ $page->exists ? 'Save page' : 'Create page' }}</button>
            <a href="{{ route('admin.pages.index') }}" class="abtn">Cancel</a>
            <span class="spacer"></span>
            @if ($page->exists && ! $page->is_system)
                <button type="submit" form="delete-page" class="abtn abtn--danger">Delete page</button>
            @endif
        </div>
    </form>

    @if ($page->exists && ! $page->is_system)
        <form id="delete-page" method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="Delete this page permanently?">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
