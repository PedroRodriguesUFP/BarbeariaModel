@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginação"
         style="display:flex; gap:.75rem; align-items:center; justify-content:center; margin:1.5rem 0;">

        @if ($paginator->onFirstPage())
            <span class="btn ghost small" aria-disabled="true">Anterior</span>
        @else
            <a class="btn ghost small" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <span>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn ghost small" href="{{ $paginator->nextPageUrl() }}" rel="next">Seguinte</a>
        @else
            <span class="btn ghost small" aria-disabled="true">Seguinte</span>
        @endif
    </nav>
@endif
