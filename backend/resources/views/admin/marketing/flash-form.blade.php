@php
    $editing = $deal->exists;
    $catalog = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->unit_price, 'img' => $p->thumbnailUrl()])->keyBy('id');
    $rows = collect(old('products', $selected->all()))->map(fn ($r, $id) => ['id' => (int) $id, 'discount' => (float) $r['discount'], 'discount_type' => $r['discount_type']])->values();
@endphp
<x-layouts.admin :title="$editing ? __('Edit Flash Deal') : __('Flash Deal Information')">
    <x-page-header :title="$editing ? __('Edit Flash Deal') : __('Flash Deal Information')">
        <a href="{{ route('admin.flash_deals.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.flash_deals.update', $deal) : route('admin.flash_deals.store') }}" class="card max-w-4xl"
          x-data="{ catalog: @js($catalog), rows: @js($rows),
                    sync(e) { const ids = [...e.target.selectedOptions].map(o => +o.value);
                              this.rows = ids.map(id => this.rows.find(r => r.id === id) || { id, discount: 0, discount_type: 'percent' }); },
                    finalPrice(r) { const p = this.catalog[r.id]?.price || 0; return Math.max(0, r.discount_type === 'percent' ? p - p * r.discount / 100 : p - r.discount).toFixed(2); } }">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body space-y-5">
            <x-form-errors />
            <div>
                <label class="form-label" for="title">{{ __('Title') }} *</label>
                <input id="title" name="title" value="{{ old('title', $deal->title) }}" required class="form-input" placeholder="Eid Mega Sale">
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="background_color">{{ __('Background Color') }}</label>
                    <input id="background_color" type="color" name="background_color" value="{{ old('background_color', $deal->background_color ?: '#f47c20') }}" class="form-input h-11 p-1">
                </div>
                <div>
                    <label class="form-label" for="text_color">{{ __('Text Color') }}</label>
                    <select id="text_color" name="text_color" class="form-input">
                        <option value="white" @selected(old('text_color', $deal->text_color) === 'white')>{{ __('White') }}</option>
                        <option value="dark" @selected(old('text_color', $deal->text_color) === 'dark')>{{ __('Dark') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="start_date">{{ __('Start Date') }} *</label>
                    <input id="start_date" name="start_date" value="{{ old('start_date', $deal->start_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time required class="form-input">
                </div>
                <div>
                    <label class="form-label" for="end_date">{{ __('End Date') }} *</label>
                    <input id="end_date" name="end_date" value="{{ old('end_date', $deal->end_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time required class="form-input">
                </div>
            </div>
            <x-upload-field name="banner_upload_id" :value="old('banner_upload_id', $deal->banner_upload_id)" :label="__('Banner')" hint="1920x500" />

            <div>
                <label class="form-label" for="deal_products">{{ __('Products') }}</label>
                <select id="deal_products" multiple x-data="tomSelect()" @change="sync($event)" placeholder="{{ __('Search products') }}">
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected($rows->contains('id', $p->id))>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200" x-show="rows.length" x-cloak>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr><th class="px-4 py-2">{{ __('Product') }}</th><th class="px-4 py-2">{{ __('Base Price') }}</th><th class="px-4 py-2">{{ __('Discount') }}</th><th class="px-4 py-2">{{ __('Discount Type') }}</th><th class="px-4 py-2">{{ __('Deal Price') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="r in rows" :key="r.id">
                            <tr>
                                <td class="px-4 py-2"><div class="flex items-center gap-2"><img :src="catalog[r.id]?.img" alt="" class="size-9 rounded object-cover"><span x-text="catalog[r.id]?.name" class="line-clamp-2"></span></div></td>
                                <td class="px-4 py-2" x-text="'৳' + (catalog[r.id]?.price || 0)"></td>
                                <td class="px-4 py-2"><input type="number" step="0.01" min="0" :name="`products[${r.id}][discount]`" x-model.number="r.discount" class="form-input w-28"></td>
                                <td class="px-4 py-2">
                                    <select :name="`products[${r.id}][discount_type]`" x-model="r.discount_type" class="form-input w-32">
                                        <option value="percent">{{ __('Percent') }}</option><option value="flat">{{ __('Flat') }}</option>
                                    </select>
                                </td>
                                <td class="px-4 py-2 font-semibold text-brand-600" x-text="'৳' + finalPrice(r)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="status" value="1" @checked(old('status', $deal->status)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"> {{ __('Active') }}
                </label>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="featured" value="1" @checked(old('featured', $deal->featured)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"> {{ __('Featured (show on home page)') }}
                </label>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
