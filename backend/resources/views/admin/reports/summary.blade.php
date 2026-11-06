<x-layouts.admin :title="__('Sales Summary Report')">
    <x-page-header :title="__('Sales Summary Report')">
        @include('admin.reports.partials.actions')
        <a href="{{ route('admin.reports.date_wise', request()->only(['range', 'date_from', 'date_to', 'delivery_status', 'payment_status'])) }}" class="btn-light no-print">{{ __('Product-Wise Details') }}</a>
    </x-page-header>

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 p-4 no-print">
        @include('admin.reports.partials.range')
        <div>
            <label class="form-label">{{ __('View Summary By') }}</label>
            <select name="view_mode" class="form-input w-auto">
                <option value="daily">{{ __('Daily Summary (Day by Day)') }}</option>
                <option value="monthly" @selected($mode === 'monthly')>{{ __('Monthly Summary (Month by Month)') }}</option>
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('Delivery Status') }}</label>
            <select name="delivery_status" class="form-input w-auto">
                <option value="delivered">{{ __('Delivered (Default)') }}</option>
                <option value="all" @selected($status === 'all')>{{ __('All Orders') }}</option>
                <option value="sales" @selected($status === 'sales')>{{ __('All except cancelled / returned') }}</option>
                @foreach (['pending', 'confirmed', 'on_the_way', 'cancelled'] as $k)<option value="{{ $k }}" @selected($status === $k)>{{ __(\App\Models\Order::DELIVERY_STATUSES[$k]) }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('Payment Status') }}</label>
            <select name="payment_status" class="form-input w-auto">
                <option value="">{{ __('All') }}</option>
                <option value="paid" @selected(request('payment_status') === 'paid')>{{ __('Paid') }}</option>
                <option value="unpaid" @selected(request('payment_status') === 'unpaid')>{{ __('Unpaid') }}</option>
            </select>
        </div>
        <button class="btn-primary">{{ __('Filter') }}</button>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        @foreach ([['Revenue', format_price($totals['revenue'])], ['Orders', $totals['orders']], ['Pieces Sold', $totals['pieces'].' Pcs'], ['Average order value', format_price($totals['aov'])]] as [$label, $value])
            <div class="card p-5"><p class="text-sm text-slate-500">{{ __($label) }}</p><p class="mt-1 text-2xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>

    @if ($rows->isNotEmpty())
        <div class="card mb-6">
            <div class="card-header"><h2 class="card-title">{{ __('Revenue Trend') }}</h2></div>
            <div class="card-body h-64" x-data="chart({ type: 'line', labels: @js($rows->map(fn ($r) => $mode === 'monthly' ? $r['date']->format('M Y') : $r['date']->format('d M'))), data: @js($rows->pluck('total')) })"><canvas x-ref="canvas"></canvas></div>
        </div>
    @endif

    <div class="card overflow-x-auto">
        <div class="card-header"><h2 class="card-title">{{ $mode === 'monthly' ? __('Monthly Sales Summary Breakdown') : __('Daily Sales Summary Breakdown') }}</h2><span class="text-sm text-slate-500">{{ $range->label() }}</span></div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">{{ __('Date') }}</th><th class="px-4 py-3 text-right">{{ __('Orders') }}</th><th class="px-4 py-3">{{ __('Invoice No(s)') }}</th><th class="px-4 py-3 text-right">{{ __('Pieces Sold') }}</th><th class="px-4 py-3 text-right">{{ __('Product Sales') }}</th><th class="px-4 py-3 text-right">{{ __('Shipping') }}</th><th class="px-4 py-3 text-right">{{ __('Discount') }}</th><th class="px-4 py-3 text-right">{{ __('Grand Total') }} (৳)</th><th class="px-4 py-3 text-right">AOV</th><th class="px-4 py-3">{{ __('Top Selling Product') }}</th><th class="px-4 py-3 no-print"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $r)
                    <tr>
                        <td class="px-4 py-2 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="whitespace-nowrap px-4 py-2">{{ $mode === 'monthly' ? $r['date']->format('F Y') : $r['date']->format('d-m-Y (D)') }}</td>
                        <td class="px-4 py-2 text-right">{{ $r['orders'] }}</td>
                        <td class="max-w-56 px-4 py-2 text-xs">@foreach ($r['codes'] as $c)<a href="{{ route('admin.orders.show', $c['id']) }}" class="mr-1 text-brand-600">#{{ $c['code'] }}</a>@endforeach</td>
                        <td class="px-4 py-2 text-right">{{ $r['pieces'] }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($r['product_sales'], 2) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($r['shipping'], 2) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($r['discount'], 2) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($r['total'], 2) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($r['aov'], 2) }}</td>
                        <td class="px-4 py-2 text-xs">{{ $r['top'] }}</td>
                        <td class="px-4 py-2 no-print">@if ($mode === 'daily')<a href="{{ route('admin.reports.date_wise', ['date_from' => $r['date']->format('Y-m-d'), 'date_to' => $r['date']->format('Y-m-d'), 'delivery_status' => $status]) }}" class="text-xs text-brand-600 hover:underline">{{ __('Details') }}</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
