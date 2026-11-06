@php($mailer = \App\Support\EnvWriter::get('MAIL_MAILER') ?? 'log')
<x-layouts.admin :title="__('SMTP Settings')">
    <x-page-header :title="__('SMTP Settings')" />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.env_key_update') }}" class="card lg:col-span-2" x-data="{ type: @js($mailer) }">
            @csrf
            <div class="card-body space-y-4">
                <div>
                    <input type="hidden" name="types[]" value="MAIL_MAILER">
                    <label class="form-label" for="MAIL_MAILER">{{ __('Type') }}</label>
                    <select id="MAIL_MAILER" name="MAIL_MAILER" x-model="type" class="form-input">
                        @foreach (['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'mailgun' => 'Mailgun', 'log' => 'Log only (testing)'] as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="type === 'smtp'" class="grid gap-4 md:grid-cols-2">
                    <x-env-setting key="MAIL_HOST" :label="__('Mail Host')" placeholder="smtp.gmail.com" />
                    <x-env-setting key="MAIL_PORT" :label="__('Mail Port')" placeholder="587" />
                    <x-env-setting key="MAIL_USERNAME" :label="__('Mail Username')" />
                    <x-env-setting key="MAIL_PASSWORD" :label="__('Mail Password')" secret />
                    <div>
                        <input type="hidden" name="types[]" value="MAIL_SCHEME">
                        <label class="form-label" for="MAIL_SCHEME">{{ __('Mail Encryption') }}</label>
                        @php($scheme = \App\Support\EnvWriter::get('MAIL_SCHEME'))
                        <select id="MAIL_SCHEME" name="MAIL_SCHEME" class="form-input">
                            <option value="null" @selected(! $scheme)>{{ __('Auto (TLS on 587)') }}</option>
                            <option value="smtps" @selected($scheme === 'smtps')>SSL (465)</option>
                        </select>
                    </div>
                </div>
                <div x-show="type === 'mailgun'" class="grid gap-4 md:grid-cols-2">
                    <x-env-setting key="MAILGUN_DOMAIN" :label="__('Mailgun Domain')" />
                    <x-env-setting key="MAILGUN_SECRET" :label="__('Mailgun Secret')" secret />
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <x-env-setting key="MAIL_FROM_ADDRESS" :label="__('Mail From Address')" placeholder="noreply@jihanmart.com" />
                    <x-env-setting key="MAIL_FROM_NAME" :label="__('Mail From Name')" placeholder="Jihan Mart" />
                </div>
                <p class="form-hint">{{ __('Gmail: use an App Password (Google Account → Security → App passwords), host smtp.gmail.com, port 587.') }}</p>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save Configuration') }}</button></div>
        </form>

        <form method="POST" action="{{ route('admin.setup.test_email') }}" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Test SMTP configuration') }}</h2></div>
            <div class="card-body space-y-3">
                <input type="email" name="email" required class="form-input" value="{{ auth()->user()->email }}" placeholder="you@example.com">
                <button class="btn-light w-full">{{ __('Send test email') }}</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
