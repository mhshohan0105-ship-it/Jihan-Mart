<x-layouts.admin :title="__('Abandoned Carts')">
    <x-page-header :title="__('Abandoned Carts')" :count="$carts->total()" />
    <div class="card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <input type="search" name="search" value="{{ request('search') }}" class="form-input min-w-56 flex-1" placeholder="{{ __('Type name, phone, address') }}">
            <select name="status" class="form-input w-auto">
                <option value="">{{ __('All Status') }}</option>
                <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                <option value="converted" @selected(request('status') === 'converted')>{{ __('Converted') }}</option>
            </select>
            <button class="btn-light">{{ __('Filter') }}</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">{{ __('Customer Name') }}</th><th class="px-4 py-3">{{ __('Phone') }}</th><th class="px-4 py-3">{{ __('Address') }}</th><th class="px-4 py-3">{{ __('District / Thana') }}</th><th class="px-4 py-3">{{ __('Cart Items') }}</th><th class="px-4 py-3">{{ __('Source') }}</th><th class="px-4 py-3">{{ __('Status') }}</th><th class="px-4 py-3">{{ __('Date & Time') }}</th><th class="px-4 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 align-top">
                    @forelse ($carts as $cart)
                        <tr x-data="{ items: false }">
                            <td class="px-4 py-3 text-slate-500">{{ $carts->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium">{{ $cart->name ?: '—' }}</td>
                            <td class="px-4 py-3"><a href="tel:{{ $cart->phone }}" class="text-brand-600">{{ $cart->phone }}</a></td>
                            <td class="max-w-56 px-4 py-3 text-xs">{{ $cart->address }}</td>
                            <td class="px-4 py-3 text-xs">{{ collect([$cart->state?->name, $cart->city?->name])->filter()->implode(' / ') }}</td>
                            <td class="px-4 py-3">
                                <button type="button" class="btn-light btn-sm" @click="items = !items">{{ count($cart->items) }} {{ __('Item(s)') }}</button>
                                <ul x-cloak x-show="items" class="mt-2 space-y-1 text-xs">
                                    @foreach ($cart->items as $item)<li>{{ $item['name'] ?? '#'.$item['product_id'] }} {{ $item['variant'] ?? '' }} × {{ $item['quantity'] }}</li>@endforeach
                                </ul>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $cart->source }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $cart->status === 'converted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ __(ucfirst($cart->status)) }}</span>@if ($cart->order_id)<p class="text-xs text-slate-500">#{{ $cart->order?->code }}</p>@endif</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs">{{ format_date($cart->updated_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    @if ($cart->status === 'pending')
                                        @can('convert_abandoned_carts')
                                            <button type="button" class="btn-primary btn-sm" @click="if (confirm(@js(__('Confirm Conversion to Order')))) window.api(@js(route('admin.abandoned.convert', $cart)), { method: 'POST' }).then((r) => { toast(r.message); location.reload() }).catch((e) => toast(e.message, 'error'))">{{ __('Convert to Order') }}</button>
                                        @endcan
                                    @endif
                                    @can('delete_abandoned_carts')<button type="button" @click="confirmDelete(@js(route('admin.abandoned.destroy', $cart)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-5 py-16 text-center text-slate-500">{{ __('Checkout details typed on the website appear here when the customer does not finish the order.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $carts->links() }}</div>
    </div>
</x-layouts.admin>
