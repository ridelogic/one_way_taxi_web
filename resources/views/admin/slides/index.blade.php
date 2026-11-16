@extends('admin.layout')

@section('title', 'Hero')

@section('content')
    <div class="page-head">
        <div>
            <h2 style="margin:0">Homepage hero</h2>
            <p>The first visible slide is the homepage hero text (headline, gold line, supporting text and buttons). Add more visible slides to rotate the text. Trust indicators and autoplay are in <a href="{{ route('admin.homepage.index') }}">Homepage sections → Hero settings</a>; the One Way / Outstation photo cards are in <a href="{{ route('admin.homepage.index') }}">Homepage sections → Booking card</a>.</p>
        </div>
        <a class="abtn abtn--primary" href="{{ route('admin.slides.create') }}"><x-icon name="plus" :size="16" /> Add slide</a>
    </div>

    <div class="section-list">
        @forelse ($slides as $slide)
            <div @class(['section-row', 'is-hidden' => ! $slide->is_active])>
                <span class="section-row__order">{{ $loop->iteration }}</span>
                <img class="section-row__thumb" src="{{ $slide->media?->safeUrl() ?? asset(\App\Models\MediaAsset::PLACEHOLDER) }}" alt="" style="object-position:{{ $slide->media?->objectPosition() }}">
                <div class="section-row__body">
                    <strong>{{ $slide->heading }} {{ $slide->highlight }}</strong>
                    <small>{{ \Illuminate\Support\Str::limit($slide->subtitle, 90) }}</small>
                </div>
                <x-admin.badge :on="$slide->is_active" on-label="Visible" off-label="Hidden" />
                <form method="POST" action="{{ route('admin.slides.move', $slide) }}" class="inline-form">@csrf @method('PATCH')<input type="hidden" name="direction" value="up"><button class="abtn abtn--sm abtn--ghost" @disabled($loop->first) aria-label="Move up">↑</button></form>
                <form method="POST" action="{{ route('admin.slides.move', $slide) }}" class="inline-form">@csrf @method('PATCH')<input type="hidden" name="direction" value="down"><button class="abtn abtn--sm abtn--ghost" @disabled($loop->last) aria-label="Move down">↓</button></form>
                <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.slides.edit', $slide) }}"><x-icon name="edit" :size="14" /> Edit</a>
                <x-admin.delete :action="route('admin.slides.destroy', $slide)" />
            </div>
        @empty
            <p class="empty">No slides yet — the homepage shows a plain heading until you add one.</p>
        @endforelse
    </div>
@endsection
