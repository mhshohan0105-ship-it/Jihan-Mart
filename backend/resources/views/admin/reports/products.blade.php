@php($title = $mode === 'sale' ? __('Product wise sale report') : __('Product wise stock report'))
<x-layouts.admin :title="$title">
    <x-page-header :title="$title">@include('admin.reports.partials.actions')</x-page-header>
    <div class="card">
        <form method="GET" class="card-header no-print">
            <label class="flex items-center gap-2 text-sm">{{ __('Sort by Category') }}:
                <select name="category_id" class="form-input w-auto" onchange="this.form.submit()">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ str_repeat('-- ', $c->depth) }}{{ $c->name }}</option>@endforeach
                </select>
            </label>
        </form>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Image') }}</th><th class="px-5 py-3">{{ __('Product Name') }}</th><th class="px-5 py-3 text-right">{{ $mode === 'sale' ? __('Num of Sale') : __('Stock') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $p)
                    <tr>
                        <td class="px-5 py-2 text-slate-500">{{ $products->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-2">@if ($img = $p->thumbnailUrl())<img src="{{ $img }}" alt="" class="size-10 rounded-lg object-cover">@endif</td>
                        <td class="px-5 py-2">
                            {{ $p->name }}
                            @if ($mode === 'stock' && $p->variant_product)<p class="text-xs text-slate-500">{{ $p->stocks->map(fn ($s) => $s->variant.': '.$s->qty)->implode(' · ') }}</p>@endif
                        </td>
                        <td class="px-5 py-2 text-right font-semibold {{ $mode === 'stock' && $p->current_stock <= (int) get_setting('low_stock_threshold', 5) ? 'text-red-600' : '' }}">{{ $mode === 'sale' ? $p->num_of_sale : $p->current_stock }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="card-body no-print">{{ $products->links() }}</div>
    </div>
</x-layouts.admin>
