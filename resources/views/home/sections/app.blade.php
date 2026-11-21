@php
    // The store buttons are part of the banner artwork. They only become links once Admin → Settings → Mobile app
    // has a real store URL (an invisible link is placed exactly over each button); until then the banner is a picture.
    $play = $appLinks->playStore();
    $ios = $appLinks->appStore();
@endphp
{{-- Book Anytime, Anywhere banner (replaces the former mobile-app promotion). Shown at its own aspect ratio — never stretched or cropped. --}}
<section class="app-band" id="app" aria-labelledby="app-title">
    <div class="container-site">
        <div class="app-band__banner">
            <h2 id="app-title" class="sr-only">{{ $section->title ?: 'Book Anytime, Anywhere' }}</h2>
            <img src="{{ asset('images/book-anytime-anywhere-banner.jpg') }}" width="1536" height="321" loading="lazy" decoding="async" class="app-band__img"
                 alt="Book Anytime, Anywhere — book rides, track your trip and manage your bookings from your phone. A smiling woman leans out of a car window.">
            @if ($play)
                <a href="{{ $play }}" target="_blank" rel="noopener" class="app-band__hit app-band__hit--play"><span class="sr-only">Get it on Google Play</span></a>
            @endif
            @if ($ios)
                <a href="{{ $ios }}" target="_blank" rel="noopener" class="app-band__hit app-band__hit--ios"><span class="sr-only">Download on the App Store</span></a>
            @endif
        </div>
    </div>
</section>
