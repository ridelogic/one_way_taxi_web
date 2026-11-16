@if ($paginator->hasPages())
    @php($base = 'grid min-w-10 h-10 place-items-center rounded-xl border px-3 text-sm font-semibold')
    <nav class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="{{ $base }} border-line text-ink-soft/50" aria-hidden="true">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page" class="{{ $base }} border-line bg-white text-forest hover:border-gold">‹</a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="{{ $base }} border-transparent text-ink-soft">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="{{ $base }} border-gold bg-gold text-white" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $base }} border-line bg-white text-forest hover:border-gold">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page" class="{{ $base }} border-line bg-white text-forest hover:border-gold">›</a>
        @else
            <span class="{{ $base }} border-line text-ink-soft/50" aria-hidden="true">›</span>
        @endif
    </nav>
@endif
