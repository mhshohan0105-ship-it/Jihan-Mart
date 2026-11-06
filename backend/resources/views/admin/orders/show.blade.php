@php
    use App\Models\Order;
    $routes = [
        'logCall' => route('admin.orders.log_call'), 'callNote' => route('admin.orders.call_note'), 'note' => route('admin.orders.note'),
        'calls' => route('admin.orders.calls', '__ID__'), 'activities' => route('admin.orders.activities', '__ID__'),
        'fraud' => route('admin.couriers.fraud'), 'fraudBatch' => route('admin.couriers.fraud_batch'), 'sendCourier' => route('admin.couriers.send'),
    ];
    $o = ['id' => $order->id, 'code' => $order->code, 'note' => $order->admin_note, 'phone' => $order->phone];
    $trackingUrl = app(\App\Services\Couriers\CourierService::class)->trackingUrl($order);
@endphp
<x-layouts.admin :title="__('Order Details').' #'.$order->code">
<div x-data="orderModals(@js($routes))" @can('view_fraud_check') x-init="loadBadges([@js($order->phone)])" @endcan>
    <x-page-header :title="__('Order Details').' #'.$order->code">
        @if ($order->consignment_id)
            @if ($trackingUrl)<a href="{{ $trackingUrl }}" target="_blank" rel="noopener" class="btn-light">{{ ucfirst($order->courier) }}: {{ $order->tracking_code ?: $order->consignment_id }}</a>@endif
        @else
            @can('send_courier')
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="btn-light" @click="open = !open">{{ __('Send Courier') }}</button>
                    <div x-cloak x-show="open" class="absolute right-0 z-20 mt-1 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl">
                        @foreach (\App\Services\Couriers\CourierService::COURIERS as $key => $label)
                            <button type="button" class="w-full px-4 py-2 text-left hover:bg-slate-50" @click="open = false; sendCourier([{{ $order->id }}], @js($key))">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            @endcan
        @endif
        @can('edit_orders')<a href="{{ route('admin.orders.edit', $order) }}" class="btn-light">{{ __('Edit Order') }}</a>@endcan
        @can('print_invoices')<a href="{{ route('admin.orders.invoice', ['id' => [$order->id]]) }}" target="_blank" class="btn-primary">{{ __('Re-Print Invoice') }}</a>@endcan
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-body grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="form-label">{{ __('Payment Status') }}</label>
                        <select class="form-input" data-current="{{ $order->payment_status }}" @disabled(! auth()->user()->can('change_payment_status'))
                                @change="setStatus(@js(route('admin.orders.payment_status')), {{ $order->id }}, $event.target.value, $event.target)">
                            @foreach (Order::PAYMENT_STATUSES as $k => $label)<option value="{{ $k }}" @selected($order->payment_status === $k)>{{ __($label) }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('Delivery Status') }}</label>
                        <select class="form-input" data-current="{{ $order->delivery_status }}" @disabled(! auth()->user()->can('change_delivery_status'))
                                @change="setStatus(@js(route('admin.orders.delivery_status')), {{ $order->id }}, $event.target.value, $event.target)">
                            @foreach (Order::DELIVERY_STATUSES as $k => $label)<option value="{{ $k }}" @selected($order->delivery_status === $k)>{{ __($label) }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('Tracking Code (optional)') }}</label>
                        <input class="form-input" value="{{ $order->tracking_code }}" @disabled(! auth()->user()->can('edit_orders'))
                               @change="window.api(@js(route('admin.orders.tracking_code')), { method: 'POST', body: { order_id: {{ $order->id }}, tracking_code: $event.target.value } }).then((r) => toast(r.message)).catch((e) => toast(e.message, 'error'))">
                    </div>
                    <div class="md:col-span-3" x-data="{ text: @js($order->admin_note) }">
                        <label class="form-label">{{ __('Comment') }}</label>
                        <div class="flex gap-2">
                            <textarea x-model="text" rows="2" class="form-input"></textarea>
                            <button type="button" class="btn-light self-end" @click="window.api(@js(route('admin.orders.note')), { method: 'POST', body: { order_id: {{ $order->id }}, admin_note: text } }).then((r) => toast(r.message)).catch((e) => toast(e.message, 'error'))">{{ __('Save Comment') }}</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">{{ __('Photo') }}</th><th class="px-4 py-3">{{ __('Description') }}</th><th class="px-4 py-3">{{ __('Qty') }}</th><th class="px-4 py-3">{{ __('Price') }}</th><th class="px-4 py-3 text-right">{{ __('Total') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->details as $d)
                            <tr>
                                <td class="px-4 py-3">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">@if ($img = $d->product?->thumbnailUrl())<img src="{{ $img }}" alt="" class="size-12 rounded-lg object-cover">@endif</td>
                                <td class="px-4 py-3"><p class="font-medium">{{ $d->product_name }}</p><p class="text-xs text-slate-500">{{ $d->variant }} @if ($d->sku) · SKU {{ $d->sku }} @endif</p></td>
                                <td class="px-4 py-3">{{ $d->quantity }}</td>
                                <td class="px-4 py-3">{{ format_price($d->price) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ format_price($d->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <dl class="ml-auto max-w-xs space-y-1 p-5 text-sm">
                    @foreach ([['Sub Total', $order->subtotal], ['Shipping', $order->shipping_cost], ['Discount', -$order->discount], ['Coupon', -$order->coupon_discount], ['Advance Paid', -$order->advance_amount]] as [$label, $value])
                        @if ((float) $value != 0 || $label === 'Sub Total' || $label === 'Shipping')<div class="flex justify-between"><dt class="text-slate-500">{{ __($label) }}</dt><dd>{{ format_price($value) }}</dd></div>@endif
                    @endforeach
                    <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold"><dt>{{ __('TOTAL') }}</dt><dd>{{ format_price($order->grand_total) }}</dd></div>
                    <div class="flex justify-between text-base font-bold text-brand-600"><dt>{{ __('TOTAL DUE') }}</dt><dd>{{ format_price($order->due) }}</dd></div>
                </dl>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Customer') }}</h2></div>
                <div class="card-body space-y-1 text-sm">
                    <p class="font-semibold">{{ $order->name }}</p>
                    <p class="flex items-center gap-2"><a href="tel:{{ $order->phone }}" @click="logCall(@js($o), $refs.calls)" class="text-brand-600 hover:underline">{{ $order->phone }}</a>
                        <button type="button" @click="openCalls(@js($o))" class="badge bg-emerald-100 text-emerald-700">📞 <span x-ref="calls">{{ $order->calls_count }}</span></button>
                        @can('view_fraud_check')<button type="button" @click="openFraud(@js($o))" class="badge" :class="{ safe: 'bg-emerald-100 text-emerald-700', medium: 'bg-amber-100 text-amber-700', risky: 'bg-red-100 text-red-700' }[badges[@js($order->phone)]?.level] || 'bg-slate-100 text-slate-600'" x-text="badges[@js($order->phone)]?.label || @js(__('Fraud Check'))"></button>@endcan</p>
                    @if ($order->email)<p>{{ $order->email }}</p>@endif
                    <p class="text-slate-600">{{ $order->address }}</p>
                    <p class="text-slate-500">{{ $order->areaLabel() }}</p>
                    @if ($order->user_id)<a href="{{ route('admin.customers.show', $order->user_id) }}" class="text-xs text-brand-600 hover:underline">{{ __('Order history') }}</a>@endif
                </div>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Order info') }}</h2></div>
                <dl class="card-body grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">{{ __('Order') }}</dt><dd>#{{ $order->code }}</dd>
                    <dt class="text-slate-500">{{ __('Order Status') }}</dt><dd><span class="badge {{ Order::STATUS_COLORS[$order->delivery_status] }}">{{ __(Order::DELIVERY_STATUSES[$order->delivery_status]) }}</span></dd>
                    <dt class="text-slate-500">{{ __('Order Date') }}</dt><dd>{{ format_date($order->created_at) }}</dd>
                    <dt class="text-slate-500">{{ __('Total amount') }}</dt><dd>{{ format_price($order->grand_total) }}</dd>
                    <dt class="text-slate-500">{{ __('Payment method') }}</dt><dd>{{ __(Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method) }}</dd>
                    @if ($order->advance_method)<dt class="text-slate-500">{{ __('Advance') }}</dt><dd>{{ ucfirst($order->advance_method) }} {{ $order->sender_no }} {{ $order->transaction_id }}</dd>@endif
                    <dt class="text-slate-500">{{ __('Source') }}</dt><dd>{{ __(Order::SOURCES[$order->source] ?? $order->source) }}@if ($order->creator) · {{ $order->creator->name }}@endif</dd>
                    @if ($order->customer_note)<dt class="text-slate-500">{{ __('Additional Info') }}</dt><dd>{{ $order->customer_note }}</dd>@endif
                </dl>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Order Activity & History Log') }} ({{ $order->activities->count() }})</h2></div>
                <div class="card-body">
                    <ol class="relative space-y-4 border-l border-slate-200 pl-5 text-sm">
                        @forelse ($order->activities as $a)
                            <li class="relative">
                                <span class="absolute -left-[27px] top-1 size-3 rounded-full ring-4 ring-white {{ \App\Models\OrderActivity::COLORS[$a->action_type] ?? 'bg-slate-400' }}"></span>
                                <p class="font-semibold">{{ $a->title }}</p>
                                @if ($a->old_value || $a->new_value)<p><span class="text-slate-400 line-through">{{ $a->old_value }}</span> @if ($a->old_value && $a->new_value)→@endif <span class="font-medium">{{ $a->new_value }}</span></p>@endif
                                @if ($a->note)<p class="text-slate-600">{{ $a->note }}</p>@endif
                                <p class="text-xs text-slate-400">{{ $a->user?->name ?? __('System') }} · {{ format_date($a->created_at) }}</p>
                            </li>
                        @empty
                            <li class="text-slate-500">{{ __('No Activity Recorded Yet') }}</li>
                        @endforelse
                    </ol>
                </div>
            </section>
        </div>
    </div>
    @include('admin.orders.partials.modals')
</div>
</x-layouts.admin>
