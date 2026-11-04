@if($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="{{ __('Páginas') }}">
        <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
        <div>
            @if($paginator->onFirstPage())
                <span class="page-disabled">{{ __('Anterior') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('Anterior') }}</a>
            @endif
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Siguiente') }}</a>
            @else
                <span class="page-disabled">{{ __('Siguiente') }}</span>
            @endif
        </div>
    </nav>
@endif
