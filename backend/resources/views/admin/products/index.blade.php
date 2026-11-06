<x-layouts.admin :title="__('All Products')">
    <div x-data="{ selected: [], all: false, toggleAll(ids) { this.selected = this.all ? [] : ids; this.all = !this.all } }">
        <x-page-header :title="__('All products')" :count="$products->total()">
            @can('delete_products')
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="btn-light" @click="open = !open" :disabled="!selected.length">
                        {{ __('Bulk Action') }} <span x-show="selected.length" x-text="'(' + selected.length + ')'"></span>
                    </button>
                    <div x-cloak x-show="open" class="absolute right-0 z-10 mt-1 w-48 rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl">
                        <button type="button" class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50"
                                @click="open = false; window.api(@js(route('admin.products.bulk_destroy')), { method: 'DELETE', body: { ids: selected } }).then((r) => { toast(r.message); location.reload() }).catch((e) => toast(e.message, 'error'))">
                            {{ __('Delete selection') }}
                        </button>
                    </div>
                </div>
            @endcan
            @can('add_products')
                <a href="{{ route('admin.products.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add New Product') }}</a>
            @endcan
        </x-page-header>

        <div class="card">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
                <select name="status" class="form-input w-auto">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="published" @selected(request('status') === 'published')>{{ __('Published') }}</option>
                    <option value="unpublished" @selected(request('status') === 'unpublished')>{{ __('Unpublished') }}</option>
                </select>
                <select name="variant" class="form-input w-auto">
                    <option value="">{{ __('All Variant') }}</option>
                    <option value="simple" @selected(request('variant') === 'simple')>{{ __('Simple') }}</option>
                    <option value="variable" @selected(request('variant') === 'variable')>{{ __('Variable') }}</option>
                </select>
                <select name="flag" class="form-input w-auto">
                    <option value="">{{ __('Select Flag') }}</option>
                    @foreach (['landing_page' => 'Landing Page', 'featured' => 'Featured', 'todays_deal' => 'Today Deal'] as $k => $label)
                        <option value="{{ $k }}" @selected(request('flag') === $k)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <select name="sort" class="form-input w-auto">
                    <option value="">{{ __('Sort By') }}</option>
                    @foreach (['sale_desc' => 'Num of Sale (High > Low)', 'sale_asc' => 'Num of Sale (Low > High)', 'stock_desc' => 'Stock (High > Low)', 'stock_asc' => 'Stock (Low > High)', 'price_desc' => 'Base Price (High > Low)', 'price_asc' => 'Base Price (Low > High)'] as $k => $label)
                        <option value="{{ $k }}" @selected(request('sort') === $k)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <input type="search" name="search" value="{{ request('search') }}" class="form-input min-w-56 flex-1" placeholder="{{ __('Name, SKU or barcode & Enter') }}">
                <button class="btn-light">{{ __('Filter') }}</button>
                @if (request()->hasAny(['status', 'variant', 'flag', 'sort', 'search']))
                    <a href="{{ route('admin.products.index') }}" class="btn-light">{{ __('Reset') }}</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3"><input type="checkbox" :checked="all" @change="toggleAll(@js($products->pluck('id')))" class="rounded border-slate-300 text-brand-500"></th>
                            <th class="px-4 py-3">{{ __('Name') }}</th>
                            <th class="px-4 py-3">{{ __('Info') }}</th>
                            <th class="px-4 py-3">{{ __('Collection') }}</th>
                            <th class="px-4 py-3">{{ __('Total Stock') }}</th>
                            <th class="px-4 py-3">{{ __('Todays Deal') }}</th>
                            <th class="px-4 py-3">{{ __('Published') }}</th>
                            <th class="px-4 py-3">{{ __('Featured') }}</th>
                            <th class="px-4 py-3">{{ __('Free Shipping') }}</th>
                            <th class="px-4 py-3">{{ __('Direct Order') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 align-top">
                        @forelse ($products as $product)
                            @php($canEdit = auth()->user()->can('edit_products'))
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3"><input type="checkbox" value="{{ $product->id }}" x-model.number="selected" class="rounded border-slate-300 text-brand-500"></td>
                                <td class="px-4 py-3">
                                    <div class="flex min-w-60 items-start gap-3">
                                        @if ($thumb = $product->thumbnailUrl())
                                            <img src="{{ $thumb }}" alt="" class="size-12 shrink-0 rounded-lg border border-slate-100 object-cover" loading="lazy">
                                        @else
                                            <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-slate-100"><x-icon name="photo" class="size-5 text-slate-400" /></span>
                                        @endif
                                        <a href="{{ $canEdit ? route('admin.products.edit', $product) : '#' }}" class="line-clamp-2 font-medium hover:text-brand-600">{{ $product->name }}</a>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs leading-5 text-slate-600">
                                    <p>{{ __('Num of Sale') }}: <b>{{ $product->num_of_sale }}</b> {{ __('times') }}</p>
                                    <p>{{ __('Base Price') }}: <b>{{ format_price($product->unit_price) }}</b></p>
                                    @if ($product->hasActiveDiscount() || $product->sale_price)
                                        <p>{{ __('Sale Price') }}: <b class="text-brand-600">{{ format_price($product->finalPrice()) }}</b></p>
                                    @endif
                                    @if ($product->sku)<p>SKU: {{ $product->sku }}</p>@endif
                                    <p>{{ __('Rating') }}: {{ $product->review_count ? $product->rating.' ★ ('.$product->review_count.')' : '—' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="min-w-40 space-y-1">
                                        @foreach ($collections as $collection)
                                            <label class="flex items-center gap-2 text-xs">
                                                <input type="checkbox" @checked($product->collections->contains('id', $collection->id)) @disabled(! $canEdit)
                                                       @change="window.api(@js(route('admin.collections.toggle_product', [$collection, $product])), { method: 'POST', body: { value: $event.target.checked ? 1 : 0 } }).then((r) => toast(r.message)).catch((e) => { toast(e.message, 'error'); $event.target.checked = !$event.target.checked })"
                                                       class="rounded border-slate-300 text-brand-500">
                                                {{ $collection->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    @if ($product->variant_product)
                                        @foreach ($product->stocks as $stock)
                                            <p class="{{ $stock->qty <= $lowStock ? 'font-semibold text-red-600' : '' }}">{{ $stock->variant }} - {{ $stock->qty }}</p>
                                        @endforeach
                                    @endif
                                    <p class="mt-1 text-sm font-semibold {{ $product->current_stock <= $lowStock ? 'text-red-600' : 'text-emerald-600' }}">{{ $product->current_stock }}</p>
                                </td>
                                @foreach (['todays_deal', 'published', 'featured', 'free_shipping', 'direct_order'] as $flag)
                                    <td class="px-4 py-3"><x-switch :url="route('admin.toggle', ['products', $product->id])" :field="$flag" :checked="$product->$flag" :disabled="! $canEdit" /></td>
                                @endforeach
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ url('/product/'.$product->slug) }}" target="_blank" rel="noopener" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" title="{{ __('View') }}"><x-icon name="external" class="size-4" /></a>
                                        @if ($canEdit)
                                            <a href="{{ route('admin.products.edit', $product) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                        @endif
                                        @can('add_products')
                                            <form method="POST" action="{{ route('admin.products.duplicate', $product) }}">
                                                @csrf
                                                <button class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" title="{{ __('Duplicate') }}"><x-icon name="document" class="size-4" /></button>
                                            </form>
                                        @endcan
                                        @can('delete_products')
                                            <button type="button" @click="confirmDelete(@js(route('admin.products.destroy', $product)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50" title="{{ __('Delete') }}"><x-icon name="trash" class="size-4" /></button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $products->links() }}</div>
        </div>
    </div>
</x-layouts.admin>
