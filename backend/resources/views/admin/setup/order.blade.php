<x-layouts.admin :title="__('Order Configuration')">
    <x-page-header :title="__('Order Configuration')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card max-w-2xl">
        @csrf
        <div class="card-header"><h2 class="card-title">{{ __('Minimum Order Amount Settings') }}</h2></div>
        <div class="card-body space-y-4">
            <x-setting key="minimum_order_amount_check" as="check" :label="__('Minimum Order Amount Check')" />
            <x-setting key="minimum_order_amount" :label="__('Set Minimum Order Amount') . ' (৳)'" placeholder="300" />
            <p class="form-hint">{{ __('Website and landing page orders below this product total are refused. Admin (POS) orders are not limited.') }}</p>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
