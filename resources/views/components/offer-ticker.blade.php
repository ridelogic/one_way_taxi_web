@props(['announcements' => null])
@php
    // Scrolling offers ticker — Admin → Promotional Announcements. Renders nothing at all (no empty bar, no space)
    // unless there are announcements to show; the homepage passes only enabled, currently valid ones.
    // Pure CSS marquee: the list is rendered twice and the track slides by exactly one copy, so the loop is seamless.
    // Pauses on hover/focus; static for reduced motion. All text is escaped ({{ }}), links are http(s) or site paths.
    $items = collect($announcements ?? [])->values();
    $seconds = max(20, min(120, 12 * max(2, $items->count()))); // about the same reading speed for any number
@endphp
@if ($items->isNotEmpty())
    <section class="offer-ticker" aria-label="Current offers" style="--offer-ticker-duration: {{ $seconds }}s">
        <div class="offer-ticker__viewport">
            <div class="offer-ticker__track">
                @foreach ([false, true] as $copy)
                    <ul class="offer-ticker__list" @if ($copy) aria-hidden="true" @endif>
                        @foreach ($items as $item)
                            <li class="offer-ticker__item">
                                @php($href = $item->linkHref())
                                @if ($href)
                                    <a href="{{ $href }}" class="offer-ticker__link" @if ($copy) tabindex="-1" @endif
                                       @if ($item->isExternalLink()) target="_blank" rel="noopener noreferrer" @endif>
                                @endif
                                <strong class="offer-ticker__highlight">{{ $item->title }}</strong>
                                <span class="offer-ticker__dash" aria-hidden="true">–</span>
                                <span class="offer-ticker__text">{{ $item->message }}</span>
                                @if ($href)</a>@endif
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </section>
@endif
