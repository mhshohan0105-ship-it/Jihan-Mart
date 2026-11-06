@php use App\Models\Order; @endphp
<x-layouts.admin :title="__('Dashboard')">
    <form method="GET" class="card mb-6 flex flex-wrap items-end justify-between gap-4 p-5 no-print">
        <div>
            <h1 class="text-2xl font-bold">{{ __('Dashboard') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }} · {{ $range->label() }}</p>
        </div>
        <div class="flex items-end gap-2">@include('admin.reports.partials.range') <button class="btn-primary">{{ __('Filter') }}</button></div>
    </form>

    {{-- KPI cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl bg-gradient-to-br from-brand-500 to-brand-400 p-5 text-white shadow-sm">
            <p class="text-sm opacity-90">{{ __('Total Sales Revenue') }}</p>
            <p class="mt-2 text-3xl font-bold">{{ format_price($kpi['revenue'], 0) }}</p>
            <p class="text-xs opacity-80">{{ $range->label() }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-navy-700 to-navy-500 p-5 text-white shadow-sm">
            <p class="text-sm opacity-90">{{ __("Today's Revenue") }}</p>
            <p class="mt-2 text-3xl font-bold">{{ format_price($kpi['today_revenue'], 0) }}</p>
            <p class="text-xs opacity-80">{{ __(':n Orders Today', ['n' => $kpi['today_orders']]) }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-leaf-500 to-emerald-500 p-5 text-white shadow-sm">
            <p class="text-sm opacity-90">{{ __('This Month Sales') }}</p>
            <p class="mt-2 text-3xl font-bold">{{ format_price($kpi['month_revenue'], 0) }}</p>
            <p class="text-xs opacity-80">{{ now()->format('F Y') }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-plum-500 to-violet-500 p-5 text-white shadow-sm">
            <p class="text-sm opacity-90">{{ __('Total Orders') }}</p>
            <p class="mt-2 text-3xl font-bold">{{ number_format($kpi['orders']) }}</p>
            @can('view_orders')<a href="{{ route('admin.orders.index') }}" class="text-xs underline opacity-90">{{ __('View All Orders') }}</a>@endcan
        </div>
    </div>

    {{-- order status summary --}}
    <div class="card mt-6">
        <div class="card-header"><h2 class="card-title">{{ __('Order Status Summary') }} ({{ $kpi['orders'] }})</h2>@can('view_orders')<a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('Manage Orders') }}</a>@endcan</div>
        <div class="card-body grid gap-3 sm:grid-cols-3 xl:grid-cols-9">
            @foreach ($kpi['statuses'] as $key => $s)
                <a href="{{ route('admin.orders.index', ['status' => $key]) }}" class="rounded-xl border border-slate-100 p-3 transition hover:border-brand-500/50 hover:shadow-sm">
                    <span class="badge {{ Order::STATUS_COLORS[$key] }}">{{ __($s['label']) }}</span>
                    <p class="mt-2 text-lg font-bold">{{ $s['count'] }}</p>
                    <p class="text-xs text-slate-500">{{ format_price($s['amount'], 0) }} · {{ $s['percent'] }}%</p>
                </a>
            @endforeach
        </div>
    </div>

    {{-- small tiles --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([['Total Customers', $tiles['customers'], 'users'], ['Active Products', $tiles['products'], 'cube'], ['Categories', $tiles['categories'], 'folder'], ['Low Stock Alert', $tiles['low_stock'], 'bell']] as [$label, $value, $icon])
            <div class="card flex items-center gap-4 p-5">
                <span class="grid size-11 place-items-center rounded-xl {{ $label === 'Low Stock Alert' && $value ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-600' }}"><x-icon :name="$icon" /></span>
                <div><p class="text-2xl font-bold">{{ number_format($value) }}</p><p class="text-sm text-slate-500">{{ __($label) }}</p></div>
            </div>
        @endforeach
        <div class="card p-5" x-data="{ d: null, async poll() { try { this.d = await window.api(@js(route('admin.dashboard.realtime'))) } catch (e) {} } }" x-init="poll(); setInterval(() => poll(), 30000)">
            <p class="flex items-center gap-2 text-sm text-slate-500"><span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>{{ __('Realtime (last 30 min)') }}</p>
            <p class="mt-1 text-2xl font-bold"><span x-text="d?.orders ?? '…'"></span> <span class="text-sm font-normal text-slate-500">{{ __('orders') }}</span></p>
            <p class="text-xs text-slate-400" x-text="d ? '৳' + Number(d.amount).toLocaleString('en-US') + ' · ' + d.at : ''"></p>
        </div>
    </div>

    {{-- charts --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Monthly Sales Overview') }} ({{ now()->year }})</h2></div>
            <div class="card-body h-72" x-data="chart({ type: 'line', labels: @js(collect(range(1, 12))->map(fn ($m) => now()->startOfYear()->month($m)->format('M'))), data: @js($charts['monthly']) })"><canvas x-ref="canvas"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Last 7 Days Sales') }}</h2></div>
            <div class="card-body h-72" x-data="chart({ type: 'bar', labels: @js($charts['daily']['labels']), data: @js($charts['daily']['values']) })"><canvas x-ref="canvas"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Category Wise Sales') }}</h2></div>
            <div class="card-body h-72">
                @if ($charts['categorySales']->isEmpty())
                    <p class="py-20 text-center text-sm text-slate-400">{{ __('No sales in this range yet') }}</p>
                @else
                    <div class="h-full" x-data="chart({ type: 'doughnut', labels: @js($charts['categorySales']->pluck('name')), data: @js($charts['categorySales']->pluck('amount')) })"><canvas x-ref="canvas"></canvas></div>
                @endif
            </div>
        </div>
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Category Wise Stock') }}</h2></div>
            <div class="card-body h-72" x-data="chart({ type: 'bar', horizontal: true, money: false, labels: @js($charts['categoryStock']->pluck('name')), data: @js($charts['categoryStock']->pluck('stock')) })"><canvas x-ref="canvas"></canvas></div>
        </div>
    </div>

    {{-- lists --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Recent Orders') }}</h2>@can('view_orders')<a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('View All Orders') }}</a>@endcan</div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recent as $o)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('admin.orders.show', $o->encryptedId()) }}" class="font-semibold text-brand-600">#{{ $o->code }}</a></td>
                            <td class="px-5 py-3"><span class="mr-2 inline-grid size-7 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-600">{{ mb_strtoupper(mb_substr($o->name, 0, 1)) }}</span>{{ $o->name }}</td>
                            <td class="px-5 py-3 font-semibold">{{ format_price($o->grand_total) }}</td>
                            <td class="px-5 py-3"><span class="badge {{ Order::STATUS_COLORS[$o->delivery_status] }}">{{ __(Order::DELIVERY_STATUSES[$o->delivery_status]) }}</span></td>
                            <td class="px-5 py-3"><span class="badge {{ $o->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ __(Order::PAYMENT_STATUSES[$o->payment_status]) }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-10 text-center text-slate-500">{{ __('No orders yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Low Stock Warning') }}</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($lowStock as $p)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            @if ($img = $p->thumbnailUrl())<img src="{{ $img }}" alt="" class="size-9 rounded-lg object-cover">@endif
                            <span class="flex-1 truncate">{{ $p->name }}</span>
                            <span class="whitespace-nowrap font-semibold {{ $p->current_stock < 0 ? 'text-red-600' : 'text-amber-600' }}">{{ $p->current_stock }} {{ __('in stock') }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-slate-500">{{ __('All products have enough stock') }}</li>
                    @endforelse
                </ul>
                <div class="px-5 pb-3">{{ $lowStock->links() }}</div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Top Selling Products') }}</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($topProducts as $p)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            @if ($img = $p->thumbnailUrl())<img src="{{ $img }}" alt="" class="size-9 rounded-lg object-cover">@endif
                            <span class="flex-1 truncate">{{ $p->name }}</span>
                            <span class="whitespace-nowrap text-xs text-slate-500">{{ $p->num_of_sale }} {{ __('Sold') }} · {{ format_price($p->finalPrice(), 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.admin>
