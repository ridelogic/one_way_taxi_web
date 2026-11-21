@extends('admin.layout')

@section('title', 'Homepage')

@section('content')
    <div class="page-head">
        <div>
            <h2 style="margin:0">Homepage sections</h2>
            <p>Edit, reorder (↑ ↓) and show/hide every homepage section. The approved hero banner and booking card always stay at the top and are not edited here.</p>
        </div>
        <div class="page-head__actions">
            <a class="abtn" href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="eye" :size="16" /> View homepage</a>
        </div>
    </div>

    <div class="section-list">
        @foreach ($sections as $section)
            <div @class(['section-row', 'is-hidden' => ! $section->is_active])>
                <span class="section-row__order">{{ $section->sort_order }}</span>
                @if ($section->media)
                    <img class="section-row__thumb" src="{{ $section->media->safeUrl() }}" alt="">
                @else
                    <span class="section-row__thumb section-row__thumb--empty" aria-hidden="true"><x-icon name="grid" :size="18" /></span>
                @endif
                <div class="section-row__body">
                    <strong>{{ $section->schema()['label'] ?? \Illuminate\Support\Str::headline($section->key) }}</strong>
                    <small>{{ \Illuminate\Support\Str::limit($section->title ?: $section->get('heading_line_1'), 80) }}</small>
                </div>
                <x-admin.badge :on="$section->is_active" on-label="Visible" off-label="Hidden" />
                <form method="POST" action="{{ route('admin.homepage.move', $section) }}" class="inline-form">@csrf @method('PATCH')<input type="hidden" name="direction" value="up"><button class="abtn abtn--sm abtn--ghost" @disabled($loop->first) aria-label="Move {{ $section->key }} up">↑</button></form>
                <form method="POST" action="{{ route('admin.homepage.move', $section) }}" class="inline-form">@csrf @method('PATCH')<input type="hidden" name="direction" value="down"><button class="abtn abtn--sm abtn--ghost" @disabled($loop->last) aria-label="Move {{ $section->key }} down">↓</button></form>
                <form method="POST" action="{{ route('admin.homepage.toggle', $section) }}" class="inline-form">
                    @csrf @method('PATCH')
                    <button type="submit" class="abtn abtn--sm">{{ $section->is_active ? 'Hide' : 'Show' }}</button>
                </form>
                <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.homepage.edit', $section) }}"><x-icon name="edit" :size="14" /> Edit</a>
            </div>
        @endforeach
    </div>

    <section class="acard" style="margin-top:24px">
        <h2 class="acard__title">Homepage SEO</h2>
        <form method="POST" action="{{ route('admin.pages.update', $page) }}">
            @csrf @method('PUT')
            <input type="hidden" name="title" value="{{ $page->title }}">
            <input type="hidden" name="slug" value="home">
            <input type="hidden" name="template" value="home">
            <input type="hidden" name="status" value="published">
            <x-admin.input name="seo_title" label="Meta title" :value="$page->seo_title" maxlength="120" help="Shown in Google results and browser tabs (about 60 characters)." />
            <x-admin.textarea name="seo_description" label="Meta description" :value="$page->seo_description" rows="3" maxlength="300" help="About 150–160 characters." />
            <button type="submit" class="abtn abtn--primary">Save SEO</button>
        </form>
    </section>
@endsection
