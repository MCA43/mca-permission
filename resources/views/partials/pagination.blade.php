@if ($paginator->hasPages())
    <nav class="mca-ui-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        @if ($paginator->onFirstPage())
            <span class="mca-ui-pagination__btn mca-ui-pagination__btn--disabled" aria-hidden="true">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="mca-ui-pagination__btn" rel="prev">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="mca-ui-pagination__dots">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="mca-ui-pagination__btn mca-ui-pagination__btn--active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="mca-ui-pagination__btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="mca-ui-pagination__btn" rel="next">&rsaquo;</a>
        @else
            <span class="mca-ui-pagination__btn mca-ui-pagination__btn--disabled" aria-hidden="true">&rsaquo;</span>
        @endif
    </nav>
@endif
