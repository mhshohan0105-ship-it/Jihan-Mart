<x-layouts.admin :title="__('Date-Wise Product Sale Report')">
    <x-page-header :title="__('Date-Wise Product Sale Report')">
        @include('admin.reports.partials.actions')
        <a href="{{ route('admin.reports.summary', request()->only(['range', 'date_from', 'date_to', 'delivery_status', 'payment_status'])) }}" class="btn-light no-print">{{ __('View Sales Summary') }}</a>
    </x-page-header>

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 p-4 no-print">
        @include('admin.reports.partials.range')
        <div>
            <label class="form-label">{{ __('Delivery Status') }}</label>
            <select name="delivery_status" class="form-input w-auto">
                <option value="delivered">{{ __('Delivered (Default)') }}</option>
                <option value="all" @selected($status === 'all')>{{ __('All Delivery Statuses') }}</option>
                <option value="sales" @selected($status === 'sales')>{{ __('All except cancelled / returned') }}</option>
                @foreach (\App\Models\Order::DELIVERY_STATUSES as $k => $label)@if ($k !== 'delivered')<option value="{{ $k }}" @selected($status === $k)>{{ __($label) }}</option>@endif @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('Report View Mode') }}</label>
            <select name="group_by" class="form-input w-auto">
                <option value="invoice">{{ __('By Invoice / Item (Detailed)') }}</option>
                <option value="date_product" @selected($group === 'date_product')>{{ __('By Date & Product (Aggregated)') }}</option>
                <option value="product" @selected($group === 'product')>{{ __('By Product (Total Period)') }}</option>
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('Category') }}</label>
            <select name="category_id" class="form-input w-auto">
                <option value="">{{ __('All Categories') }}</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ str_repeat('-- ', $c->depth) }}{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('Payment') }}</label>
            <select name="payment_status" class="form-input w-auto">
                <option value="">{{ __('All') }}</option>
                <option value="paid" @selected(request('payment_status') === 'paid')>{{ __('Paid') }}</option>
                <option value="unpaid" @selected(request('payment_status') === 'unpaid')>{{ __('Unpaid') }}</option>
            </select>
        </div>
        <input name="search" value="{{ request('search') }}" class="form-input w-48" placeholder="{{ __('Product name / Order code') }}">
        <button class="btn-primary">{{ __('Filter') }}</button>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        @foreach ([['Total Sales', format_price($totals['amount'])], ['Pieces Sold', $totals['pieces'].' Pcs'], ['Orders', $totals['orders']], ['Products', $totals['products']]] as [$label, $value])
            <div class="card p-5"><p class="text-sm text-slate-500">{{ __($label) }}</p><p class="mt-1 text-2xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <div class="card-header"><h2 class="card-title">{{ __('Detailed Sales Report') }}</h2><span class="text-sm text-slate-500">{{ $range->label() }}</span></div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">{{ __('Date') }}</th><th class="px-4 py-3">{{ __('Invoice No') }}</th><th class="px-4 py-3">{{ __('Image') }}</th><th class="px-4 py-3">{{ __('Product Name') }}</th><th class="px-4 py-3">{{ __('Variation') }}</th><th class="px-4 py-3">{{ __('Category') }}</th><th class="px-4 py-3 text-right">{{ __('Pieces Sold') }}</th><th class="px-4 py-3 text-right">{{ __('Avg Unit Price') }}</th><th class="px-4 py-3 text-right">{{ __('Total Sales') }} (৳)</th><th class="px-4 py-3">{{ __('Payment') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $d)
                    <tr>
                        <td class="px-4 py-2 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="whitespace-nowrap px-4 py-2">{{ $group === 'product' ? '—' : format_date($d->order->created_at, false) }}</td>
                        <td class="px-4 py-2">@if ($group === 'invoice')<a href="{{ route('admin.orders.show', $d->order->encryptedId()) }}" class="font-semibold text-brand-600">#{{ $d->order->code }}</a>@else — @endif</td>
                        <td class="px-4 py-2">@if ($img = $d->product?->thumbnailUrl())<img src="{{ $img }}" alt="" class="size-9 rounded object-cover">@endif</td>
                        <td class="px-4 py-2">{{ $d->product_name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $d->variant ?: '—' }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $d->product?->categories->pluck('name')->implode(', ') }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $d->quantity }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($d->price, 2) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($d->total, 2) }}</td>
                        <td class="px-4 py-2 text-xs">{{ __(\App\Models\Order::PAYMENT_STATUSES[$d->order->payment_status] ?? '') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($group === 'invoice')<div class="card-body no-print">{{ $rows->links() }}</div>@endif
    </div>
</x-layouts.admin>
