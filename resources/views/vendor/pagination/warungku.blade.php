@if ($paginator->hasPages())
    {{-- Paginasi sederhana: tanpa SVG bawaan Laravel agar seragam dengan tema. --}}
    @if ($paginator->onFirstPage())
        <span class="mati">&laquo;</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="mati">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="aktif">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
    @else
        <span class="mati">&raquo;</span>
    @endif
@endif
