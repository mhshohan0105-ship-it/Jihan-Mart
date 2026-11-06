@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="{{ __('Page navigation') }}">
        <p class="text-slate-500">
            {{ __('Showing :from to :to of :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>
        <div class="flex flex-wrap gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg px-3 py-1.5 text-slate-300">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg px-3 py-1.5 hover:bg-slate-100" rel="prev">‹</a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 py-1.5 text-slate-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="rounded-lg bg-brand-500 px-3 py-1.5 font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="rounded-lg px-3 py-1.5 hover:bg-slate-100">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg px-3 py-1.5 hover:bg-slate-100" rel="next">›</a>
            @else
                <span class="rounded-lg px-3 py-1.5 text-slate-300">›</span>
            @endif
        </div>
    </nav>
@endif
