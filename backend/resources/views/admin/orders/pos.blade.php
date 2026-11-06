@php
    $editing = (bool) $order;
    $config = [
        'routes' => [
            'products' => route('admin.orders.pos_products'),
            'customers' => route('admin.orders.customer_search'),
            'save' => $editing ? route('admin.orders.update', $order) : route('admin.orders.store'),
        ],
        'method' => $editing ? 'PUT' : 'POST',
        'states' => $states->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'cities' => $s->cities->map->only(['id', 'name', 'cost'])->values()]),
        'freeShippingMin' => setting_on('free_shipping_enabled') ? (float) get_setting('free_shipping_min_amount', 0) : 0,
    ];
    if ($editing) {
        $config += [
            'cart' => $order->details->map(fn ($d) => [
                'product_id' => $d->product_id, 'name' => $d->product_name, 'image' => $d->product?->thumbnailUrl(), 'variant' => $d->variant,
                'price' => (float) $d->price, 'quantity' => $d->quantity,
                'max' => (int) ($d->product?->stocks->firstWhere('variant', $d->variant)?->qty ?? 0) + $d->quantity,
            ]),
            'customer' => ['name' => $order->name, 'phone' => $order->phone, 'email' => $order->email, 'address' => $order->address, 'state_id' => $order->state_id, 'city_id' => $order->city_id],
            'discount' => (float) $order->discount, 'discountType' => 'flat', 'shippingOverride' => (float) $order->shipping_cost,
            'advance' => (float) $order->advance_amount, 'advanceMethod' => $order->advance_method ?: 'cod',
            'senderNo' => $order->sender_no, 'trxId' => $order->transaction_id, 'note' => $order->customer_note,
        ];
    }
@endphp
<x-layouts.admin :title="$editing ? __('Edit Order').' #'.$order->code : __('Create New Order')">
<div x-data="posScreen(@js($config))">
    <x-page-header :title="$editing ? __('Edit Order').' #'.$order->code : __('Create New Order')">
        <a href="{{ route('admin.orders.index') }}" class="btn-light">{{ __('Back to All Orders') }}</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-5">
        {{-- product picker --}}
        <section class="card xl:col-span-3">
            <div class="flex flex-wrap gap-2 border-b border-slate-100 p-4">
                <input x-model="search" @input.debounce.400ms="reload()" class="form-input min-w-56 flex-1" placeholder="{{ __('Search by Product Name, SKU or Barcode...') }}">
                <select x-model="category" @change="reload()" class="form-input w-auto">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}">{{ str_repeat('-- ', $c->depth) }}{{ $c->name }}</option>@endforeach
                </select>
                <select x-model="brand" @change="reload()" class="form-input w-auto">
                    <option value="">{{ __('All Brands') }}</option>
                    @foreach ($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
            </div>
            <div class="grid max-h-[70vh] grid-cols-2 gap-3 overflow-y-auto p-4 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="p in products" :key="p.id">
                    <button type="button" @click="pick(p)" :disabled="p.stock <= 0" class="overflow-hidden rounded-xl border border-slate-200 bg-white text-left transition hover:border-brand-500 hover:shadow disabled:opacity-40">
                        <div class="aspect-square bg-slate-50"><img x-show="p.image" :src="p.image" alt="" class="size-full object-cover" loading="lazy"></div>
                        <div class="p-2">
                            <p class="line-clamp-2 text-xs font-medium" x-text="p.name"></p>
                            <p class="mt-1 flex items-center justify-between text-xs"><b class="text-brand-600" x-text="money(p.price)"></b><span class="text-slate-500" x-text="p.stock + ' {{ __('in stock') }}'"></span></p>
                        </div>
                    </button>
                </template>
                <p x-show="!loading && !products.length" class="col-span-full py-12 text-center text-sm text-slate-500">{{ __('Nothing found') }}</p>
                <div class="col-span-full text-center" x-show="page < lastPage"><button type="button" class="btn-light" @click="more()">{{ __('Load more') }}</button></div>
            </div>
        </section>

        {{-- cart + customer --}}
        <section class="space-y-4 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Select Customer') }}</h2></div>
                <div class="card-body space-y-3">
                    <div class="relative">
                        <input x-model="customerQuery" @input.debounce.300ms="findCustomers()" class="form-input" placeholder="{{ __('Search existing customer by name or phone') }}">
                        <div x-show="customerResults.length" class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                            <template x-for="c in customerResults" :key="c.id"><button type="button" @click="useCustomer(c)" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"><b x-text="c.name"></b> <span class="text-slate-500" x-text="c.phone"></span></button></template>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <input x-model="customer.name" class="form-input" placeholder="{{ __('Name') }} *">
                        <input x-model="customer.phone" class="form-input" placeholder="01XXXXXXXXX *">
                    </div>
                    <textarea x-model="customer.address" rows="2" class="form-input" placeholder="{{ __('Full address') }} *"></textarea>
                    <div class="grid grid-cols-2 gap-3">
                        <select x-model="customer.state_id" class="form-input"><option value="">{{ __('District / Zone') }}</option><template x-for="s in states"><option :value="s.id" x-text="s.name" :selected="String(s.id) === String(customer.state_id)"></option></template></select>
                        <select x-model="customer.city_id" class="form-input"><option value="">{{ __('Area / Thana') }}</option><template x-for="c in cities"><option :value="c.id" x-text="c.name + ' (৳' + Number(c.cost) + ')'" :selected="String(c.id) === String(customer.city_id)"></option></template></select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Cart') }} (<span x-text="cart.length"></span>)</h2></div>
                <div class="divide-y divide-slate-100">
                    <template x-for="(l, i) in cart" :key="l.product_id + l.variant">
                        <div class="flex items-center gap-3 p-3 text-sm">
                            <img x-show="l.image" :src="l.image" alt="" class="size-12 rounded-lg object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium" x-text="l.name"></p>
                                <p class="text-xs text-slate-500" x-text="l.variant"></p>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="number" min="0" step="0.01" x-model.number="l.price" class="form-input w-24 py-1 text-xs" title="{{ __('Unit price') }}">
                                    <div class="flex items-center rounded-lg border border-slate-200">
                                        <button type="button" class="px-2" @click="l.quantity > 1 ? l.quantity-- : cart.splice(i, 1)">−</button>
                                        <input type="number" min="1" :max="l.max" x-model.number="l.quantity" class="w-12 border-0 p-0 text-center text-xs focus:ring-0">
                                        <button type="button" class="px-2" @click="l.quantity < l.max ? l.quantity++ : toast('{{ __('No more stock') }}', 'warning')">+</button>
                                    </div>
                                </div>
                            </div>
                            <b x-text="money(l.price * l.quantity)"></b>
                            <button type="button" class="text-red-500" @click="cart.splice(i, 1)"><x-icon name="trash" class="size-4" /></button>
                        </div>
                    </template>
                    <p x-show="!cart.length" class="p-6 text-center text-sm text-slate-500">{{ __('Click a product to add it') }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body space-y-3 text-sm">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">{{ __('Discount') }}</label><div class="flex gap-1"><input type="number" min="0" x-model.number="discount" class="form-input"><select x-model="discountType" class="form-input w-20"><option value="flat">৳</option><option value="percent">%</option></select></div></div>
                        <div><label class="form-label">{{ __('Shipping') }}</label><input type="number" min="0" x-model="shippingOverride" :placeholder="areaCost" class="form-input"><p class="form-hint">{{ __('Empty = area charge') }}</p></div>
                        <div><label class="form-label">{{ __('Advance Amount') }}</label><input type="number" min="0" x-model.number="advance" class="form-input"></div>
                        <div><label class="form-label">{{ __('Payment Method') }}</label><select x-model="advanceMethod" class="form-input"><option value="cod">{{ __('Cash on Delivery') }}</option><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="rocket">Rocket</option></select></div>
                        <div x-show="advance > 0"><label class="form-label">{{ __('Sender Phone (Optional)') }}</label><input x-model="senderNo" class="form-input"></div>
                        <div x-show="advance > 0"><label class="form-label">{{ __('TrxID (Optional)') }}</label><input x-model="trxId" class="form-input"></div>
                    </div>
                    <textarea x-model="note" rows="2" class="form-input" placeholder="{{ __('Order Instructions / Notes (Optional)') }}"></textarea>
                    <dl class="space-y-1 border-t border-slate-100 pt-3">
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Sub Total') }}</dt><dd x-text="money(subtotal)"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Shipping') }}</dt><dd x-text="money(shipping)"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Discount') }}</dt><dd x-text="'-' + money(discountAmount)"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Advance') }}</dt><dd x-text="'-' + money(advance)"></dd></div>
                        <div class="flex justify-between text-base font-bold"><dt>{{ __('Total') }}</dt><dd x-text="money(total)"></dd></div>
                        <div class="flex justify-between font-semibold text-brand-600"><dt>{{ __('Due') }}</dt><dd x-text="money(due)"></dd></div>
                    </dl>
                    <button type="button" class="btn-primary w-full py-3" :disabled="saving || !cart.length" @click="submit()">{{ $editing ? __('Update Order') : __('Place & Create Order') }}</button>
                </div>
            </div>
        </section>
    </div>

    {{-- variant chooser --}}
    <div x-cloak x-show="chooser" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/50 p-4" @keydown.escape.window="chooser = null">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl" @click.outside="chooser = null">
            <h3 class="font-semibold" x-text="chooser?.name"></h3>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <template x-for="s in chooser?.stocks || []">
                    <button type="button" :disabled="s.qty <= 0" @click="add(chooser, s)" class="rounded-xl border border-slate-200 p-3 text-left text-sm hover:border-brand-500 disabled:opacity-40">
                        <b x-text="s.variant"></b><p class="text-xs text-slate-500"><span x-text="money(s.price)"></span> · <span x-text="s.qty"></span> {{ __('in stock') }}</p>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
</x-layouts.admin>
