<x-layouts.admin :title="__('Courier API')">
    <x-page-header :title="__('Courier API')" />

    @php($chip = fn (bool $on) => $on ? '<span class="badge bg-emerald-100 text-emerald-700">'.e(__('Configured (.env)')).'</span>' : '<span class="badge bg-slate-100 text-slate-500">'.e(__('Not Set (.env)')).'</span>')
    @php($test = fn (string $c) => "window.api('".route('admin.couriers.test', $c)."', { method: 'POST' }).then((r) => toast(r.message)).catch((e) => toast(e.message, 'error'))")

    <form method="POST" action="{{ route('admin.couriers.settings.save') }}" class="grid max-w-6xl gap-6 xl:grid-cols-2">
        @csrf
        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Steadfast Courier & Webhook API') }}</h2>{!! $chip($steadfastOn) !!}</div>
            <div class="card-body space-y-4">
                <x-form-errors />
                <p class="form-hint mt-0">{{ __('Steadfast panel → API → Create API keys. Leave a field empty to keep the saved value.') }}</p>
                @foreach (['STEADFAST_API_KEY' => 'API Key', 'STEADFAST_SECRET_KEY' => 'Secret Key'] as $key => $label)
                    <div>
                        <label class="form-label" for="{{ $key }}">{{ __($label) }} *</label>
                        <input id="{{ $key }}" name="{{ $key }}" type="password" autocomplete="off" class="form-input" placeholder="{{ $masked[$key] ?: __('Not set') }}">
                    </div>
                @endforeach
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm" x-data>
                    <p class="font-semibold">{{ __('Webhook (automatic status updates)') }}</p>
                    <p class="form-hint">{{ __('Works once the website is live on the internet. In the Steadfast panel → Webhook, paste this URL and the token below.') }}</p>
                    <div class="mt-2 flex gap-2">
                        <input readonly value="{{ $webhookUrl }}" class="form-input bg-white text-xs">
                        <button type="button" class="btn-light btn-sm" @click="navigator.clipboard.writeText(@js($webhookUrl)).then(() => toast(@js(__('Copied'))))">{{ __('Copy') }}</button>
                    </div>
                    <label class="form-label mt-3" for="STEADFAST_WEBHOOK_SECRET">{{ __('Webhook Secret (bearer token)') }}</label>
                    <input id="STEADFAST_WEBHOOK_SECRET" name="STEADFAST_WEBHOOK_SECRET" type="password" autocomplete="off" class="form-input" placeholder="{{ $masked['STEADFAST_WEBHOOK_SECRET'] ?: __('Not set') }}">
                    <label class="mt-2 flex items-center gap-2"><input type="checkbox" name="generate_webhook_secret" value="1" class="rounded border-slate-300 text-brand-500"> {{ __('Generate a new random token') }}</label>
                    @if (config('couriers.steadfast.webhook_secret'))
                        <p class="form-hint">{{ __('Current token') }}: <code class="select-all rounded bg-white px-1">{{ config('couriers.steadfast.webhook_secret') }}</code></p>
                    @endif
                </div>
                <button type="button" class="btn-light" @click="{{ $test('steadfast') }}" @disabled(! $steadfastOn)>{{ __('Test Steadfast Connection') }}</button>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Pathao Courier API') }}</h2>{!! $chip($pathaoOn) !!}</div>
            <div class="card-body space-y-4">
                <p class="form-hint mt-0">{{ __('Pathao merchant panel → Developers API. Areas must be linked to Pathao city/zone in Shipping Cities.') }}</p>
                @foreach (['PATHAO_CLIENT_ID' => 'Client ID', 'PATHAO_CLIENT_SECRET' => 'Client Secret', 'PATHAO_USERNAME' => 'Username / Email', 'PATHAO_PASSWORD' => 'Password', 'PATHAO_STORE_ID' => 'Store ID'] as $key => $label)
                    <div>
                        <label class="form-label" for="{{ $key }}">{{ __($label) }}</label>
                        <input id="{{ $key }}" name="{{ $key }}" type="{{ in_array($key, ['PATHAO_USERNAME', 'PATHAO_STORE_ID']) ? 'text' : 'password' }}" autocomplete="off" class="form-input" placeholder="{{ $masked[$key] ?: __('Not set') }}">
                    </div>
                @endforeach
                <button type="button" class="btn-light" @click="{{ $test('pathao') }}" @disabled(! $pathaoOn)>{{ __('Test Pathao Connection') }}</button>
            </div>
        </section>

        <section class="card xl:col-span-2">
            <div class="card-body grid gap-4 md:grid-cols-3 md:items-end">
                <div>
                    <label class="form-label" for="default_courier">{{ __('Default courier for "Send Courier"') }}</label>
                    <select id="default_courier" name="default_courier" class="form-input">
                        @foreach (\App\Services\Couriers\CourierService::COURIERS as $k => $label)<option value="{{ $k }}" @selected(get_setting('default_courier', 'steadfast') === $k)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="courier_sent_status">{{ __('Status after sending') }}</label>
                    <select id="courier_sent_status" name="courier_sent_status" class="form-input">
                        <option value="">{{ __('Do not change') }}</option>
                        @foreach (\App\Models\Order::DELIVERY_STATUSES as $k => $label)<option value="{{ $k }}" @selected(get_setting('courier_sent_status', 'picked_up') === $k)>{{ __($label) }}</option>@endforeach
                    </select>
                </div>
                <div class="text-right"><button class="btn-primary">{{ __('Save to .env') }}</button></div>
            </div>
        </section>
    </form>
</x-layouts.admin>
