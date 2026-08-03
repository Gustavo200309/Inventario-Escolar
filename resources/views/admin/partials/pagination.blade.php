@php
    $paginator = $paginator ?? null;
    $perPageName = $perPageName ?? 'per_page';
    $currentPerPage = $currentPerPage ?? (int) request()->query($perPageName, $paginator?->perPage() ?? 25);
    $selectId = 'per-page-select-' . $perPageName;
@endphp

@if($paginator && $paginator->total() > 0)
    <div class="paginacion" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <span>Mostrando {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }} registro(s)</span>

        <form method="GET" action="{{ url()->current() }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            @foreach(request()->except([$perPageName, 'page']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="{{ $selectId }}" style="font-size:13px;font-weight:600;color:var(--muted);">Por p&aacute;gina</label>
            <select id="{{ $selectId }}" name="{{ $perPageName }}" onchange="this.form.submit()" style="min-width:90px;padding:8px 10px;border:1px solid var(--border);border-radius:10px;background:var(--surface-strong);color:var(--text);">
                @foreach([10, 20, 25, 50] as $option)
                    <option value="{{ $option }}" {{ $currentPerPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        </form>

        @if($paginator->hasPages())
            <div class="paginas">
                @if($paginator->onFirstPage())
                    <button disabled>&laquo;</button>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}"><button>&laquo;</button></a>
                @endif
                @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}"><button class="{{ $page == $paginator->currentPage() ? 'pagina-activa' : '' }}">{{ $page }}</button></a>
                @endforeach
                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}"><button>&raquo;</button></a>
                @else
                    <button disabled>&raquo;</button>
                @endif
            </div>
        @endif
    </div>
@endif
