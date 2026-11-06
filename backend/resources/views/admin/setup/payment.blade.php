<x-layouts.admin :title="__('Payment Methods')">
    <x-page-header :title="__('Payment Methods')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card mb-6 max-w-5xl">
        @csrf
        <div class="card-header"><h2 class="card-title">{{ __('Manual Payment (send money + Transaction ID)') }}</h2></div>
        <div class="card-body space-y-3">
            <input type="hidden" name="types[]" value="manual_payment_numbers">
            <x-repeater name="manual_payment_numbers" :rows="get_setting('manual_payment_numbers')"
                        :fields="['gateway' => ['text', 'bKash / Nagad / Rocket'], 'number' => ['text', '01XXXXXXXXX'], 'type' => ['text', 'Personal / Merchant']]" />
            <p class="form-hint">{{ __('Shown on checkout when the customer picks that method. Turn methods on or off in Features activation.') }}</p>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>

    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 max-w-5xl">
        {{ __('bKash merchant (online payment): save the credentials from your bKash merchant panel here. Checkout will use them once the bKash payment integration is switched on.') }}
    </div>
    <form method="POST" action="{{ route('admin.env_key_update') }}" class="grid max-w-2xl gap-6">
        @csrf
        <section class="card">
            <div class="card-header"><h2 class="card-title">bKash</h2></div>
            <div class="card-body space-y-4">
                <x-env-setting key="BKASH_CHECKOUT_APP_KEY" label="App Key" secret />
                <x-env-setting key="BKASH_CHECKOUT_APP_SECRET" label="App Secret" secret />
                <x-env-setting key="BKASH_CHECKOUT_USER_NAME" label="Username" />
                <x-env-setting key="BKASH_CHECKOUT_PASSWORD" label="Password" secret />
                <x-env-setting key="BKASH_SANDBOX" :label="__('Sandbox (true / false)')" placeholder="true" />
            </div>
        </section>
        <div class="flex justify-end"><button class="btn-primary">{{ __('Save credentials') }}</button></div>
    </form>
</x-layouts.admin>
