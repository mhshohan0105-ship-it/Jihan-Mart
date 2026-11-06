@props(['title', 'count' => null])
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="flex items-center gap-2 text-2xl font-bold">
        {{ $title }}
        @if ($count !== null)
            <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-sm font-semibold text-brand-600">{{ number_format($count) }}</span>
        @endif
    </h1>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
