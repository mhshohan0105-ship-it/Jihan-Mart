@php
    $editing = $product->exists;
    $selectedCategories = old('category_ids', $product->categories?->pluck('id')->all() ?? []);
    $stockRows = $product->stocks?->keyBy('variant') ?? collect();
    $simple = $stockRows->get('');
    $initial = [
        'unitPrice' => (float) old('unit_price', $product->unit_price ?? 0),
        'colors' => array_map('strval', old('colors', $product->colors ?? [])),
        'colorNames' => $colors->pluck('name', 'id'),
        'attributes' => $attributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'values' => $a->values->pluck('value')]),
        'choices' => old('choice_options', $product->choice_options ?? []),
        'stocks' => old('stocks', $stockRows->except('')->map(fn ($s) => ['price' => (float) $s->price, 'sku' => $s->sku, 'qty' => $s->qty, 'image_upload_id' => $s->image_upload_id])),
        'tiers' => old('tiers', $product->tiers?->map(fn ($t) => ['min_qty' => $t->min_qty, 'price' => (float) $t->price])->values() ?? []),
        'policy' => old('payment_policy', $product->payment_policy),
        'discountType' => old('discount_type', $product->discount_type),
    ];
@endphp
<x-layouts.admin :title="$editing ? __('Edit Product') : __('Add New Product')">
    <x-page-header :title="$editing ? __('Edit Product') : __('Add New Product')">
        @if ($editing)
            <a href="{{ url('/product/'.$product->slug) }}" target="_blank" rel="noopener" class="btn-light"><x-icon name="external" class="size-4" /> {{ __('View') }}</a>
        @endif
        <a href="{{ route('admin.products.index') }}" class="btn-light">{{ __('All Products') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
          x-data="productForm(@js($initial))" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <x-form-errors class="mb-0" />

            {{-- Product Information --}}
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Product Information') }}</h2></div>
                <div class="card-body grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="form-label" for="name">{{ __('Product Name') }} *</label>
                        <input id="name" name="name" value="{{ old('name', $product->name) }}" required class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label" for="category_ids">{{ __('Category') }} *</label>
                        <select id="category_ids" name="category_ids[]" multiple x-data="tomSelect()" placeholder="{{ __('Select categories') }}">
                            @foreach ($tree as $node)
                                <option value="{{ $node->id }}" @selected(in_array($node->id, $selectedCategories))>{{ str_repeat('‑‑ ', $node->depth) }}{{ $node->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="brand_id">{{ __('Brand') }}</label>
                        <select id="brand_id" name="brand_id" x-data="tomSelect()">
                            <option value="">{{ __('Select Brand') }}</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="suggestion_category_id">{{ __('Suggestion Category') }}</label>
                        <select id="suggestion_category_id" name="suggestion_category_id" x-data="tomSelect()">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($tree as $node)
                                <option value="{{ $node->id }}" @selected(old('suggestion_category_id', $product->suggestion_category_id) == $node->id)>{{ str_repeat('‑‑ ', $node->depth) }}{{ $node->name }}</option>
                            @endforeach
                        </select>
                        <p class="form-hint">{{ __('Related products on the product page come from here.') }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="unit">{{ __('Unit') }}</label>
                        <input id="unit" name="unit" value="{{ old('unit', $product->unit) }}" placeholder="{{ __('Unit (e.g. KG, Pc etc)') }}" class="form-input">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label" for="weight">{{ __('Weight (In Kg)') }}</label>
                            <input id="weight" name="weight" type="number" step="0.001" min="0" value="{{ old('weight', $product->weight) }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" for="min_qty">{{ __('Minimum Purchase Qty') }} *</label>
                            <input id="min_qty" name="min_qty" type="number" min="1" value="{{ old('min_qty', $product->min_qty ?? 1) }}" required class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="tags">{{ __('Tags') }}</label>
                        <input id="tags" name="tags" value="{{ old('tags', $product->tags) }}" x-data="tomSelect()" data-tags placeholder="{{ __('Type and hit enter to add a tag') }}">
                    </div>
                    <div>
                        <label class="form-label" for="barcode">{{ __('Barcode') }}</label>
                        <input id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-input" placeholder="{{ __('Auto generated when empty') }}">
                    </div>
                </div>
            </section>

            {{-- Images + video --}}
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Product Images') }}</h2></div>
                <div class="card-body grid gap-5 md:grid-cols-2">
                    <x-upload-field name="photos" :value="old('photos', $product->photos)" multiple :label="__('Gallery Images')" hint="600x600" />
                    <div>
                        <x-upload-field name="thumbnail_upload_id" :value="old('thumbnail_upload_id', $product->thumbnail_upload_id)" :label="__('Thumbnail Image')" hint="300x300" />
                        @if ($product->image_url && ! $product->thumbnail_upload_id)
                            <p class="form-hint">{{ __('Currently using an imported image') }}: <a href="{{ $product->image_url }}" target="_blank" rel="noopener" class="text-brand-600 underline">{{ __('open') }}</a></p>
                        @endif
                    </div>
                    <div>
                        <label class="form-label" for="video_provider">{{ __('Video Provider') }}</label>
                        <select id="video_provider" name="video_provider" class="form-input">
                            <option value="">{{ __('None') }}</option>
                            @foreach (['youtube' => 'Youtube', 'dailymotion' => 'Dailymotion', 'vimeo' => 'Vimeo'] as $k => $label)
                                <option value="{{ $k }}" @selected(old('video_provider', $product->video_provider) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="video_link">{{ __('Video Link') }}</label>
                        <input id="video_link" name="video_link" type="url" value="{{ old('video_link', $product->video_link) }}" class="form-input" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                </div>
            </section>

            {{-- Variation --}}
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Product Variation') }}</h2></div>
                <div class="card-body space-y-5">
                    <div class="grid gap-4 md:grid-cols-[auto_1fr] md:items-center">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" x-model="colorsOn" class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"> {{ __('Colors') }}
                        </label>
                        <div :class="!colorsOn && 'pointer-events-none opacity-50'">
                            <select name="colors[]" multiple x-data="tomSelect({ render: { option: (d, e) => `<div class='flex items-center gap-2'><span class='size-4 rounded-full border' style='background:${d.code}'></span>${e(d.text)}</div>`, item: (d, e) => `<div class='flex items-center gap-1.5'><span class='size-3 rounded-full' style='background:${d.code}'></span>${e(d.text)}</div>` } })"
                                    @change="colors = [...$event.target.selectedOptions].map((o) => o.value)" :disabled="!colorsOn" placeholder="{{ __('Select colors') }}">
                                @foreach ($colors as $color)
                                    <option value="{{ $color->id }}" data-code="{{ $color->code }}" @selected(in_array((string) $color->id, $initial['colors'], true))>{{ $color->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">{{ __('Attributes') }}</label>
                        <select multiple x-data="tomSelect()" @change="syncChoices([...$event.target.selectedOptions].map((o) => o.value))" placeholder="{{ __('Choose attributes (Size, Shade, Pcs...)') }}">
                            @foreach ($attributes as $attribute)
                                <option value="{{ $attribute->id }}" @selected(collect($initial['choices'])->pluck('attribute_id')->map('intval')->contains($attribute->id))>{{ $attribute->name }}</option>
                            @endforeach
                        </select>
                        <p class="form-hint">{{ __('Choose the attributes of this product and then input values of each attribute.') }}</p>
                    </div>
                    <template x-for="(choice, i) in choices" :key="choice.attribute_id">
                        <div class="grid gap-3 md:grid-cols-[160px_1fr] md:items-start">
                            <input type="hidden" :name="`choice_options[${i}][attribute_id]`" :value="choice.attribute_id">
                            <template x-for="v in choice.values" :key="v"><input type="hidden" :name="`choice_options[${i}][values][]`" :value="v"></template>
                            <p class="pt-2 text-sm font-semibold" x-text="attributeName(choice.attribute_id)"></p>
                            <div><select multiple x-init="valuePicker($el, choice)" placeholder="{{ __('Type a value and press Enter') }}"></select></div>
                        </div>
                    </template>

                    {{-- variant combination table --}}
                    <div x-show="variants.length" x-cloak class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-3 py-2">{{ __('Variant') }}</th><th class="px-3 py-2">{{ __('Variant Price') }}</th><th class="px-3 py-2">SKU</th><th class="px-3 py-2">{{ __('Quantity') }}</th><th class="px-3 py-2">{{ __('Photo') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="v in variants" :key="v">
                                    <tr>
                                        <td class="px-3 py-2 font-medium" x-text="v"></td>
                                        <td class="px-3 py-2"><input type="number" step="0.01" min="0" :name="`stocks[${v}][price]`" x-model="stock(v).price" class="form-input w-28"></td>
                                        <td class="px-3 py-2"><input :name="`stocks[${v}][sku]`" x-model="stock(v).sku" class="form-input w-32"></td>
                                        <td class="px-3 py-2"><input type="number" :name="`stocks[${v}][qty]`" x-model="stock(v).qty" class="form-input w-24"></td>
                                        <td class="px-3 py-2">
                                            <div x-data="uploadField({ value: String(stock(v).image_upload_id || '') })" x-effect="stock(v).image_upload_id = value" class="flex items-center gap-2">
                                                <input type="hidden" :name="`stocks[${v}][image_upload_id]`" :value="value">
                                                <button type="button" @click="browse()" class="grid size-10 place-items-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                                                    <template x-if="files[0]"><img :src="files[0].url" alt="" class="size-full object-cover"></template>
                                                    <template x-if="!files[0]"><x-icon name="photo" class="size-4 text-slate-400" /></template>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- Price + stock --}}
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Product price + stock') }}</h2></div>
                <div class="card-body grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="unit_price">{{ __('Unit price') }} * (৳)</label>
                        <input id="unit_price" name="unit_price" type="number" step="0.01" min="0" x-model.number="unitPrice" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="sale_price">{{ __('Sale price') }} (৳)</label>
                        <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price) }}" class="form-input">
                    </div>
                    <div class="md:col-span-2 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label" for="discount_start_date">{{ __('Discount starts') }}</label>
                            <input id="discount_start_date" name="discount_start_date" value="{{ old('discount_start_date', $product->discount_start_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time class="form-input">
                        </div>
                        <div>
                            <label class="form-label" for="discount_end_date">{{ __('Discount ends') }}</label>
                            <input id="discount_end_date" name="discount_end_date" value="{{ old('discount_end_date', $product->discount_end_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="discount">{{ __('Discount') }} *</label>
                        <div class="flex gap-2">
                            <input id="discount" name="discount" type="number" step="0.01" min="0" value="{{ old('discount', $product->discount ?? 0) }}" required class="form-input">
                            <select name="discount_type" x-model="discountType" class="form-input w-32">
                                <option value="flat">{{ __('Flat') }} ৳</option>
                                <option value="percent">{{ __('Percent') }} %</option>
                            </select>
                        </div>
                    </div>
                    <div x-show="!variants.length">
                        <label class="form-label" for="qty">{{ __('Quantity') }} *</label>
                        <input id="qty" name="qty" type="number" value="{{ old('qty', $simple?->qty ?? 0) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="sku">SKU</label>
                        <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" class="form-input">
                    </div>
                    <div class="grid grid-cols-2 gap-3 md:col-span-2">
                        <div>
                            <label class="form-label" for="external_link">{{ __('External link') }}</label>
                            <input id="external_link" name="external_link" type="url" value="{{ old('external_link', $product->external_link) }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" for="external_link_btn">{{ __('External link button text') }}</label>
                            <input id="external_link_btn" name="external_link_btn" value="{{ old('external_link_btn', $product->external_link_btn) }}" class="form-input">
                        </div>
                    </div>
                </div>
            </section>

            {{-- Tiers --}}
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Quantity-Based Tiered Pricing') }}</h2>
                    <button type="button" class="btn-light btn-sm" @click="addTier()"><x-icon name="plus" class="size-4" /> {{ __('Add Tier') }}</button>
                </div>
                <div class="card-body space-y-3">
                    <p class="form-hint mt-0" x-show="!tiers.length">{{ __('Example: buy 3 or more for ৳90 each.') }}</p>
                    <template x-for="(tier, i) in tiers" :key="i">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="form-label">{{ __('Min Quantity') }}</label>
                                <input type="number" min="2" :name="`tiers[${i}][min_qty]`" x-model="tier.min_qty" class="form-input" placeholder="2, 5, 10">
                            </div>
                            <div class="flex-1">
                                <label class="form-label">{{ __('Tier Unit Price') }} (৳)</label>
                                <input type="number" step="0.01" min="0" :name="`tiers[${i}][price]`" x-model="tier.price" class="form-input">
                            </div>
                            <button type="button" @click="tiers.splice(i, 1)" class="mb-1 grid size-9 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Description, PDF, SEO --}}
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Product Description') }}</h2></div>
                <div class="card-body" x-data="richText()">
                    <textarea x-ref="input" name="description" class="hidden">{{ old('description', $product->description) }}</textarea>
                    <div x-ref="editor" class="min-h-48 rounded-b-xl bg-white"></div>
                </div>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('PDF Specification') }} · {{ __('SEO Meta Tags') }}</h2></div>
                <div class="card-body grid gap-5 md:grid-cols-2">
                    <x-upload-field name="pdf_upload_id" :value="old('pdf_upload_id', $product->pdf_upload_id)" type="document" :label="__('PDF Specification')" />
                    <x-upload-field name="meta_upload_id" :value="old('meta_upload_id', $product->meta_upload_id)" :label="__('Meta Image')" />
                    <div>
                        <label class="form-label" for="meta_title">{{ __('Meta Title') }}</label>
                        <input id="meta_title" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="meta_description">{{ __('Meta Description') }}</label>
                        <textarea id="meta_description" name="meta_description" rows="2" class="form-input">{{ old('meta_description', $product->meta_description) }}</textarea>
                    </div>
                </div>
            </section>
        </div>

        {{-- right column --}}
        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Payment Policy') }}</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label" for="payment_policy">{{ __('Payment Requirement') }}</label>
                        <select id="payment_policy" name="payment_policy" x-model="policy" class="form-input">
                            @foreach (\App\Models\Product::PAYMENT_POLICIES as $k => $label)
                                <option value="{{ $k }}">{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="policy === 'partial'" x-cloak class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label" for="partial_payment_type">{{ __('Advance Type') }}</label>
                            <select id="partial_payment_type" name="partial_payment_type" class="form-input">
                                <option value="flat" @selected(old('partial_payment_type', $product->partial_payment_type) === 'flat')>{{ __('Fixed Amount (Flat)') }}</option>
                                <option value="percent" @selected(old('partial_payment_type', $product->partial_payment_type) === 'percent')>{{ __('Percentage (%)') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="partial_advance_amount">{{ __('Advance Amount') }}</label>
                            <input id="partial_advance_amount" name="partial_advance_amount" type="number" step="0.01" min="0" value="{{ old('partial_advance_amount', $product->partial_advance_amount ?? 0) }}" class="form-input">
                        </div>
                    </div>
                    <p class="form-hint" x-show="policy === 'prepaid'" x-cloak>{{ __('Cash on delivery is not offered for this product. The customer pays in full by bKash, Nagad or card.') }}</p>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Visibility') }}</h2></div>
                <div class="card-body space-y-3">
                    @foreach ([
                        'featured' => ['Featured', 'Show in featured sections.'],
                        'todays_deal' => ['Todays Deal', 'Show in today\'s deals.'],
                        'free_shipping' => ['Free Shipping', 'No delivery charge for this product.'],
                        'direct_order' => ['Direct Order (Details Page)', 'Quick order form (name, phone, address) on the product page.'],
                        'landing_page' => ['Landing Page', 'Available for landing pages.'],
                    ] as $flag => [$label, $hint])
                        <label class="flex items-start justify-between gap-3">
                            <span><span class="block text-sm font-medium">{{ __($label) }}</span><span class="block text-xs text-slate-500">{{ __($hint) }}</span></span>
                            <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, $product->$flag)) class="mt-1 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Collections') }}</h2></div>
                <div class="card-body">
                    <select name="collection_ids[]" multiple x-data="tomSelect()" placeholder="{{ __('Add to collections') }}">
                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}" @selected(in_array($collection->id, old('collection_ids', $product->exists ? $product->collections->pluck('id')->all() : [])))>{{ $collection->name }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Estimate Shipping Time') }}</h2></div>
                <div class="card-body">
                    <label class="form-label" for="shipping_days">{{ __('Shipping Days') }}</label>
                    <input id="shipping_days" name="shipping_days" type="number" min="0" value="{{ old('shipping_days', $product->shipping_days) }}" class="form-input" placeholder="2">
                    <label class="form-label mt-4" for="warranty">{{ __('Warranty') }}</label>
                    <input id="warranty" name="warranty" value="{{ old('warranty', $product->warranty) }}" maxlength="60" class="form-input" placeholder="৬ মাস / ১ বছর / ৫ বছর">
                    <p class="form-hint">{{ __('Leave empty if the product has no warranty. Shown on the product page.') }}</p>
                </div>
            </section>

            @if ($taxes->isNotEmpty())
                <section class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Vat & TAX') }}</h2></div>
                    <div class="card-body space-y-4">
                        @foreach ($taxes as $tax)
                            @php($pt = $productTaxes[$tax->id] ?? null)
                            <div>
                                <label class="form-label" for="tax_{{ $tax->id }}">{{ $tax->name }}</label>
                                <div class="flex gap-2">
                                    <input id="tax_{{ $tax->id }}" type="number" step="0.01" min="0" name="taxes[{{ $tax->id }}][tax]" value="{{ old('taxes.'.$tax->id.'.tax', $pt ? (float) $pt->tax : '') }}" class="form-input" placeholder="0">
                                    <select name="taxes[{{ $tax->id }}][tax_type]" class="form-input w-28">
                                        <option value="percent" @selected(old('taxes.'.$tax->id.'.tax_type', $pt?->tax_type) !== 'flat')>%</option>
                                        <option value="flat" @selected(old('taxes.'.$tax->id.'.tax_type', $pt?->tax_type) === 'flat')>৳</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach
                        <p class="form-hint">{{ __('Added on top of the selling price shown to customers.') }}</p>
                    </div>
                </section>
            @endif

            <div class="sticky bottom-4 flex gap-2 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
                <button name="button" value="unpublish" class="btn-light flex-1">{{ __('Save & Unpublish') }}</button>
                <button name="button" value="publish" class="btn-primary flex-1">{{ __('Save & Publish') }}</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
