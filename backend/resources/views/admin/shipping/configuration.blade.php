<x-layouts.admin :title="__('Shipping Configuration')">
    <x-page-header :title="__('Shipping Configuration')" />

    <form method="POST" action="{{ route('admin.shipping.configuration.save') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2" x-data="{ type: @js(get_setting('shipping_type', 'area_wise')) }">
        @csrf
        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Select Shipping Method') }}</h2></div>
            <div class="card-body space-y-3">
                <x-form-errors />
                @foreach (['area_wise' => ['Area Wise Shipping Cost', 'Each area / thana has its own charge (Shipping Cities).'], 'flat_rate' => ['Flat Rate Shipping Cost', 'One charge for every order.']] as $key => [$label, $hint])
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4" :class="type === @js($key) ? 'border-brand-500 bg-brand-50/60' : 'border-slate-200'">
                        <input type="radio" name="shipping_type" value="{{ $key }}" x-model="type" class="mt-1 text-brand-500 focus:ring-brand-500">
                        <span><span class="block font-semibold">{{ __($label) }}</span><span class="text-sm text-slate-500">{{ __($hint) }}</span></span>
                    </label>
                @endforeach
                <div x-show="type === 'flat_rate'">
                    <label class="form-label" for="flat_rate_shipping_cost">{{ __('Flat Rate Cost') }} (৳)</label>
                    <input id="flat_rate_shipping_cost" name="flat_rate_shipping_cost" type="number" step="0.01" min="0" value="{{ get_setting('flat_rate_shipping_cost', 0) }}" class="form-input">
                </div>
                <p class="form-hint" x-show="type === 'area_wise'">{{ __('Area charges') }}: <a href="{{ route('admin.shipping.cities') }}" class="text-brand-600 underline">{{ __('Shipping Cities') }}</a></p>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Minimum Order Amount for Free Shipping') }}</h2></div>
            <div class="card-body space-y-4">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="free_shipping_enabled" value="1" @checked(setting_on('free_shipping_enabled')) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                    {{ __('Enable Free Shipping Offer') }}
                </label>
                <div>
                    <label class="form-label" for="free_shipping_min_amount">{{ __('Minimum Order Amount') }} (৳)</label>
                    <input id="free_shipping_min_amount" name="free_shipping_min_amount" type="number" step="0.01" min="0" value="{{ get_setting('free_shipping_min_amount', 2000) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="default_courier">{{ __('Default courier') }}</label>
                    <select id="default_courier" name="default_courier" class="form-input">
                        @foreach (['steadfast' => 'Steadfast', 'pathao' => 'Pathao'] as $k => $label)
                            <option value="{{ $k }}" @selected(get_setting('default_courier', 'steadfast') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">{{ __('Courier API settings (Steadfast, Pathao) arrive in phase 4.') }}</p>
                </div>
            </div>
        </section>
        <div class="lg:col-span-2 text-right"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
