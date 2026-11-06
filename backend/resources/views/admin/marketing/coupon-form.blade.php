@php
    $editing = $coupon->exists;
    $pickedProducts = collect(old('product_ids', $coupon->product_ids ?? []))->map(fn ($v) => (int) $v);
    $pickedUsers = collect(old('user_ids', $coupon->user_ids ?? []))->map(fn ($v) => (int) $v);
@endphp
<x-layouts.admin :title="$editing ? __('Edit Coupon') : __('Coupon Information')">
    <x-page-header :title="$editing ? __('Edit Coupon') : __('Coupon Information')">
        <a href="{{ route('admin.coupons.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="card max-w-3xl" x-data="{ type: @js(old('type', $coupon->type)) }">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body space-y-5">
            <x-form-errors />
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="type">{{ __('Coupon Type') }} *</label>
                    <select id="type" name="type" x-model="type" class="form-input">
                        @foreach (\App\Models\Coupon::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="code">{{ __('Coupon Code') }} *</label>
                    <input id="code" name="code" value="{{ old('code', $coupon->code) }}" required class="form-input font-mono uppercase" placeholder="EID100">
                </div>
            </div>

            <div x-show="type === 'product'" x-cloak>
                <label class="form-label" for="product_ids">{{ __('Products') }} *</label>
                <select id="product_ids" name="product_ids[]" multiple x-data="tomSelect()" placeholder="{{ __('Search products') }}">
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected($pickedProducts->contains($p->id))>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div x-show="type === 'cart'" class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="min_buy">{{ __('Minimum Shopping') }}</label>
                    <input id="min_buy" type="number" step="0.01" min="0" name="min_buy" value="{{ old('min_buy', $coupon->min_buy) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="max_discount">{{ __('Maximum Discount Amount') }}</label>
                    <input id="max_discount" type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="user_ids">{{ __('Only for these customers') }}</label>
                    <select id="user_ids" name="user_ids[]" multiple x-data="tomSelect()" placeholder="{{ __('All customers') }}">
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected($pickedUsers->contains($c->id))>{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="discount">{{ __('Discount') }} *</label>
                    <input id="discount" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', $coupon->discount) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label" for="discount_type">{{ __('Discount Type') }}</label>
                    <select id="discount_type" name="discount_type" class="form-input">
                        <option value="flat" @selected(old('discount_type', $coupon->discount_type) === 'flat')>{{ __('Flat') }}</option>
                        <option value="percent" @selected(old('discount_type', $coupon->discount_type) === 'percent')>{{ __('Percent') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="usage_limit">{{ __('Total Usage Limit') }}</label>
                    <input id="usage_limit" type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-input" placeholder="{{ __('Unlimited') }}">
                </div>
                <div>
                    <label class="form-label" for="usage_limit_per_user">{{ __('Usage Limit Per Customer') }}</label>
                    <input id="usage_limit_per_user" type="number" min="1" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" class="form-input" placeholder="{{ __('Unlimited') }}">
                </div>
                <div>
                    <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
                    <input id="start_date" name="start_date" value="{{ old('start_date', $coupon->start_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time class="form-input">
                </div>
                <div>
                    <label class="form-label" for="end_date">{{ __('End Date') }}</label>
                    <input id="end_date" name="end_date" value="{{ old('end_date', $coupon->end_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time class="form-input">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="status" value="1" @checked(old('status', $coupon->status)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"> {{ __('Active') }}
            </label>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
