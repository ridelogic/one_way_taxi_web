@props(['videos', 'title', 'subtitle' => null, 'eyebrow' => 'Video stories', 'headingId' => 'videos-title', 'class' => 'bg-white'])
{{--
    YouTube video gallery (Admin → Video Promotions), used on the homepage and on district pages.
    Thumbnails are lazy-loaded images; no YouTube player is created until a visitor clicks a video, and only one player
    exists at a time (inside the shared modal). If YouTube refuses to play a video in the player (owner restrictions,
    age checks, removed video…) the modal swaps to the thumbnail with a "Watch on YouTube" button — never a blank box.
    Videos whose owner has turned off embedding are shown as "Watch on YouTube" cards from the start.
    Without JavaScript every card is a normal link to the video on YouTube.
--}}
@if ($videos->isNotEmpty())
    <section {{ $attributes->class(['home-section', $class]) }} aria-labelledby="{{ $headingId }}">
        <div class="container-site">
            <x-home-heading :eyebrow="$eyebrow" :title="$title" :subtitle="$subtitle" :id="$headingId" />
            <ul class="video-grid">
                @foreach ($videos as $video)
                    @php($embeddable = $video->canEmbed())
                    <li>
                        <a href="{{ $video->watchUrl() }}" target="_blank" rel="noopener" @class(['video-card', 'video-card--external' => ! $embeddable])
                           @if ($embeddable) data-video-id="{{ $video->youtube_id }}" data-video-title="{{ $video->title }}" data-video-thumb="{{ $video->thumbnailUrl() }}" @endif>
                            <span class="video-card__media">
                                <img src="{{ $video->thumbnailUrl() }}" alt="" loading="lazy" decoding="async" width="480" height="360" class="video-card__img">
                                <span class="video-card__tag">{{ $video->categoryLabel() }}</span>
                                @if ($embeddable)
                                    <span class="video-card__play" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l10.6-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14Z"/></svg></span>
                                @else
                                    <span class="video-card__yt"><x-icon name="youtube" :size="18" /> Watch on YouTube</span>
                                @endif
                            </span>
                            <span class="video-card__body">
                                <span class="video-card__title">{{ $video->title }}</span>
                                @if ($video->description)
                                    <span class="video-card__text">{{ $video->description }}</span>
                                @endif
                                <span class="sr-only">{{ $embeddable ? '— play video' : '— opens on YouTube in a new tab' }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    @once
        @push('scripts')
            <dialog class="video-modal" data-video-modal aria-label="Video player">
                <div class="video-modal__box">
                    <div class="video-modal__bar">
                        <p class="video-modal__title" data-video-modal-title></p>
                        <button type="button" class="video-modal__close" data-video-modal-close aria-label="Close video"><x-icon name="x" :size="22" /></button>
                    </div>
                    <div class="video-modal__frame" data-video-modal-frame></div>
                    {{-- Shown instead of the player when YouTube does not allow this video to play here --}}
                    <div class="video-modal__fallback" data-video-fallback hidden>
                        <img alt="" data-video-fallback-img class="video-modal__fallback-img">
                        <div class="video-modal__fallback-body">
                            <p class="video-modal__fallback-text">This video can’t be played on our website, but you can watch it on YouTube.</p>
                            <a href="#" target="_blank" rel="noopener" class="btn btn-gold" data-video-fallback-link><x-icon name="youtube" :size="18" /> Watch on YouTube</a>
                        </div>
                    </div>
                </div>
            </dialog>
        @endpush
    @endonce
@endif
