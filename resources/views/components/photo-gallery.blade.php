@props(['photos', 'title', 'subtitle' => null, 'eyebrow' => 'Gallery', 'headingId' => 'gallery-title', 'class' => 'bg-white'])
{{--
    Photo gallery (homepage or a district page). Thumbnails are lazy-loaded; clicking one opens the full photo in a
    shared lightbox. Without JavaScript each photo links to the image file. Renders nothing when there are no photos.
--}}
@if ($photos->isNotEmpty())
    <section {{ $attributes->class(['home-section', $class]) }} aria-labelledby="{{ $headingId }}">
        <div class="container-site">
            <x-home-heading :eyebrow="$eyebrow" :title="$title" :subtitle="$subtitle" :id="$headingId" />
            <ul class="photo-gallery">
                @foreach ($photos as $photo)
                    <li>
                        <a href="{{ $photo->media->safeUrl() }}" class="photo-gallery__item" data-lightbox-src="{{ $photo->media->safeUrl() }}" data-lightbox-title="{{ $photo->title }}">
                            <x-img :media="$photo->media" :alt="$photo->altText()" class="photo-gallery__img" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 33vw, 50vw" />
                            @if ($photo->title || $photo->description)
                                <span class="photo-gallery__caption">
                                    @if ($photo->title)<strong>{{ $photo->title }}</strong>@endif
                                    @if ($photo->description)<span>{{ $photo->description }}</span>@endif
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    @once
        @push('scripts')
            <dialog class="video-modal" data-lightbox aria-label="Photo">
                <div class="video-modal__box">
                    <div class="video-modal__bar">
                        <p class="video-modal__title" data-lightbox-title-out></p>
                        <button type="button" class="video-modal__close" data-lightbox-close aria-label="Close photo"><x-icon name="x" :size="22" /></button>
                    </div>
                    <img class="lightbox__img" data-lightbox-img alt="">
                </div>
            </dialog>
        @endpush
    @endonce
@endif
