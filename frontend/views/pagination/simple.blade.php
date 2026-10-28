@if($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Páginas">
        <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
        <div>
            @if($paginator->onFirstPage())
                <span class="page-disabled">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
                <span class="page-disabled">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
