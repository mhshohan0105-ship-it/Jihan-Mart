@php
    $routes = [
        'logCall' => route('admin.orders.log_call'), 'callNote' => route('admin.orders.call_note'), 'note' => route('admin.orders.note'),
        'calls' => route('admin.orders.calls', '__ID__'), 'activities' => route('admin.orders.activities', '__ID__'),
        'fraud' => route('admin.couriers.fraud'), 'fraudBatch' => route('admin.couriers.fraud_batch'), 'sendCourier' => route('admin.couriers.send'),
    ];
    $canStatus = auth()->user()->can('change_delivery_status');
    $total = $counts->sum();
@endphp
<x-layouts.admin :title="$title ?? __('All Orders')">
<div x-data="Object.assign(orderModals(@js($routes)), { selected: [], all: false })" @can('view_fraud_check') x-init="loadBadges(@js($orders->pluck('phone')->unique()->values()))" @endcan>
    <x-page-header :title="$title ?? __('All Orders')" :count="$orders->total()">
        @can('create_orders')<a href="{{ route('admin.orders.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Create Order') }}</a>@endcan
        @can('print_invoices')<a href="{{ route('admin.orders.checklist') }}" target="_blank" class="btn-light">{{ __('All Confirmed Checklist') }} ({{ $confirmedCount }})</a>@endcan
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="btn-light" @click="open = !open">{{ __('Bulk Action') }} <span x-show="selected.length" x-text="'(' + selected.length + ')'"></span></button>
            <div x-cloak x-show="open" class="absolute right-0 z-20 mt-1 w-64 rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl">
                @can('print_invoices')
                    <button type="button" class="w-full px-4 py-2 text-left hover:bg-slate-50" :disabled="!selected.length" @click="window.open(@js(route('admin.orders.invoice')) + '?' + selected.map((i) => 'id[]=' + i).join('&'))">{{ __('Bulk Invoice Print') }}</button>
                    <button type="button" class="w-full px-4 py-2 text-left hover:bg-slate-50" :disabled="!selected.length" @click="window.open(@js(route('admin.orders.checklist')) + '?' + selected.map((i) => 'id[]=' + i).join('&'))">{{ __('Packaging Checklist (Selected)') }}</button>
                    <a href="{{ route('admin.orders.checklist') }}" target="_blank" class="block px-4 py-2 hover:bg-slate-50">{{ __('Packaging Checklist (All Confirmed)') }} {{ $confirmedCount }}</a>
                @endcan
                @can('send_courier')
                    <button type="button" class="w-full px-4 py-2 text-left hover:bg-slate-50" :disabled="!selected.length" @click="open = false; sendCourier(selected)">{{ __('Bulk send courier') }}</button>
                @endcan
                @can('delete_orders')
                    <button type="button" class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50" :disabled="!selected.length"
                            @click="window.api(@js(route('admin.orders.bulk_destroy')), { method: 'DELETE', body: { ids: selected } }).then((r) => { toast(r.message); location.reload() }).catch((e) => toast(e.message, 'error'))">{{ __('Delete selection') }}</button>
                @endcan
            </div>
        </div>
    </x-page-header>

    {{-- status pills --}}
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.orders.index', request()->except(['status', 'page'])) }}" class="rounded-full border px-4 py-1.5 font-medium {{ ! $status ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-200 bg-white' }}">{{ __('All') }} <span class="opacity-75">{{ $total }}</span></a>
        @foreach (\App\Models\Order::DELIVERY_STATUSES as $key => $label)
            <a href="{{ route('admin.orders.index', array_merge(['status' => $key], request()->except(['status', 'page']))) }}" class="rounded-full border px-4 py-1.5 font-medium {{ $status === $key ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-200 bg-white hover:border-brand-500/50' }}">{{ __($label) }} <span class="opacity-75">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4" x-data="{ state: @js(request('state_id')), cities: [] , async loadCities() { this.cities = this.state ? (await window.api(@js(route('admin.get_cities')), { method: 'POST', body: { state_id: this.state } })).data : [] } }" x-init="loadCities()">
            @if (request('source'))<input type="hidden" name="source" value="{{ request('source') }}">@endif
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="search" value="{{ request('search') }}" class="form-input min-w-56 flex-1" placeholder="{{ __('Code, Name, Phone, Email...') }}">
            <input name="date_from" value="{{ request('date_from') }}" x-data="datePicker()" class="form-input w-36" placeholder="{{ __('From') }}">
            <input name="date_to" value="{{ request('date_to') }}" x-data="datePicker()" class="form-input w-36" placeholder="{{ __('To') }}">
            <select name="payment_status" class="form-input w-auto">
                <option value="">{{ __('All Payment') }}</option>
                @foreach (\App\Models\Order::PAYMENT_STATUSES as $k => $label)<option value="{{ $k }}" @selected(request('payment_status') === $k)>{{ __($label) }}</option>@endforeach
            </select>
            <select name="state_id" x-model="state" @change="loadCities()" class="form-input w-auto">
                <option value="">{{ __('All District') }}</option>
                @foreach ($states as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
            <select name="city_id" class="form-input w-auto">
                <option value="">{{ __('All Area') }}</option>
                <template x-for="c in cities"><option :value="c.id" x-text="c.name" :selected="String(c.id) === @js((string) request('city_id'))"></option></template>
            </select>
            <button class="btn-primary">{{ __('Apply Filter') }}</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3"><input type="checkbox" class="rounded border-slate-300 text-brand-500" @change="selected = $event.target.checked ? @js($orders->pluck('id')) : []"></th>
                        <th class="px-3 py-3">{{ __('Order Code') }}</th><th class="px-3 py-3">{{ __('Total Item') }}</th><th class="px-3 py-3">{{ __('Customer') }}</th>
                        <th class="px-3 py-3">{{ __('Fraud') }}</th><th class="px-3 py-3">{{ __('Date & Time') }}</th><th class="px-3 py-3">{{ __('Amount') }}</th>
                        <th class="px-3 py-3">{{ __('Delivery Status') }}</th><th class="px-3 py-3">{{ __('Comment') }}</th><th class="px-3 py-3">{{ __('Payment') }}</th><th class="px-3 py-3 text-right">{{ __('Options') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 align-top">
                    @forelse ($orders as $order)
                        @php($o = ['id' => $order->id, 'code' => $order->code, 'note' => $order->admin_note, 'phone' => $order->phone])
                        <tr class="hover:bg-slate-50/60" x-data="{ note: @js($order->admin_note) }" @order-note.window="$event.detail.id === {{ $order->id }} && (note = $event.detail.note)">
                            <td class="px-3 py-3"><input type="checkbox" value="{{ $order->id }}" x-model.number="selected" class="rounded border-slate-300 text-brand-500"></td>
                            <td class="px-3 py-3 font-semibold">
                                <a href="{{ route('admin.orders.show', $order->encryptedId()) }}" class="hover:text-brand-600">#{{ $order->code }}</a>
                                @unless ($order->viewed)<span class="badge ml-1 bg-brand-500 text-white">{{ __('New') }}</span>@endunless
                                <p class="text-xs font-normal text-slate-400">{{ __(\App\Models\Order::SOURCES[$order->source] ?? $order->source) }}{{ $order->landing_domain ? ' · '.$order->landing_domain : '' }}</p>
                            </td>
                            <td class="px-3 py-3">{{ $order->itemCount() }}</td>
                            <td class="px-3 py-3">
                                <p class="font-medium">{{ $order->name }}</p>
                                <p class="flex items-center gap-1.5">
                                    <a href="tel:{{ $order->phone }}" @click="logCall(@js($o), $refs.calls)" class="text-brand-600 hover:underline">{{ $order->phone }}</a>
                                    <button type="button" @click="openCalls(@js($o))" class="badge bg-emerald-100 text-emerald-700" title="{{ __('Call History') }}">📞 <span x-ref="calls">{{ $order->calls_count }}</span></button>
                                </p>
                                <p class="text-xs text-slate-500">{{ $order->areaLabel() }}</p>
                            </td>
                            <td class="px-3 py-3">@can('view_fraud_check')<button type="button" @click="openFraud(@js($o))" class="badge whitespace-nowrap"
                                        :class="{ safe: 'bg-emerald-100 text-emerald-700', medium: 'bg-amber-100 text-amber-700', risky: 'bg-red-100 text-red-700', new: 'bg-slate-100 text-slate-600' }[badges[@js($order->phone)]?.level] || 'bg-slate-100 text-slate-400'"
                                        x-text="badges[@js($order->phone)] ? (badges[@js($order->phone)].rate !== null ? badges[@js($order->phone)].rate + '%' : @js(__('New'))) : '…'"></button>@endcan</td>
                            <td class="whitespace-nowrap px-3 py-3 text-xs">{{ format_date($order->created_at) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 font-semibold">{{ format_price($order->grand_total) }}@if ((float) $order->advance_amount > 0)<p class="text-xs font-normal text-slate-500">{{ __('Due') }} {{ format_price($order->due) }}</p>@endif</td>
                            <td class="px-3 py-3">
                                @if ($canStatus)
                                    <select class="form-input w-40 py-1.5 text-xs font-semibold {{ \App\Models\Order::STATUS_COLORS[$order->delivery_status] }}" data-current="{{ $order->delivery_status }}"
                                            @change="setStatus(@js(route('admin.orders.delivery_status')), {{ $order->id }}, $event.target.value, $event.target)">
                                        @foreach (\App\Models\Order::DELIVERY_STATUSES as $k => $label)<option value="{{ $k }}" @selected($order->delivery_status === $k)>{{ __($label) }}</option>@endforeach
                                    </select>
                                @else
                                    <span class="badge {{ \App\Models\Order::STATUS_COLORS[$order->delivery_status] }}">{{ __(\App\Models\Order::DELIVERY_STATUSES[$order->delivery_status]) }}</span>
                                @endif
                                @if ($order->consignment_id)
                                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($order->courier) }} · {{ $order->tracking_code ?: $order->consignment_id }}</p>
                                @endif
                            </td>
                            <td class="max-w-48 px-3 py-3 text-xs">
                                <p class="line-clamp-2 text-slate-600" x-text="note"></p>
                                <button type="button" class="text-brand-600 hover:underline" @click="openComment(Object.assign(@js($o), { note }))" x-text="note ? @js(__('Edit')) : @js(__('Add Comment'))"></button>
                            </td>
                            <td class="px-3 py-3 text-xs">
                                <p>{{ __(\App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method) }}</p>
                                <span class="badge {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($order->payment_status === 'partial_paid' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ __(\App\Models\Order::PAYMENT_STATUSES[$order->payment_status]) }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end" x-data="floatingMenu()" @click.outside="close()" @keydown.escape.window="close()">
                                    <button type="button" class="btn-light btn-sm" x-ref="button" @click="toggle()" :aria-expanded="open.toString()">{{ __('Actions') }}</button>
                                    {{-- fixed position so the scrolling table does not cut it off --}}
                                    <div x-cloak x-show="open" x-ref="menu" :style="style" class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl">
                                        <a href="{{ route('admin.orders.show', $order->encryptedId()) }}" class="block px-4 py-2 hover:bg-slate-50">{{ __('View') }}</a>
                                        @can('print_invoices')<a href="{{ route('admin.orders.invoice', ['id' => [$order->id]]) }}" target="_blank" class="block px-4 py-2 hover:bg-slate-50">{{ __('Download Invoice') }}</a>@endcan
                                        @can('send_courier')@unless ($order->consignment_id)<button type="button" class="w-full px-4 py-2 text-left hover:bg-slate-50" @click="open = false; sendCourier([{{ $order->id }}])">{{ __('Send Courier') }}</button>@endunless @endcan
                                        @can('edit_orders')<a href="{{ route('admin.orders.edit', $order) }}" class="block px-4 py-2 hover:bg-slate-50">{{ __('Edit') }}</a>@endcan
                                        <button type="button" class="w-full px-4 py-2 text-left hover:bg-slate-50" @click="open = false; openActivity(@js($o))">{{ __('Activity Log') }}</button>
                                        @can('delete_orders')<button type="button" class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50" @click="open = false; confirmDelete(@js(route('admin.orders.destroy', $order)), { message: @js(__('Stock of this order is returned to the shelf.')) })">{{ __('Delete') }}</button>@endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-5 py-16 text-center text-slate-500">{{ __('Showing all latest orders. Use filters above to narrow down results.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $orders->links() }}</div>
    </div>

    @include('admin.orders.partials.modals')
</div>
</x-layouts.admin>
