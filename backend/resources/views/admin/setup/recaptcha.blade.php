<x-layouts.admin :title="__('Google reCAPTCHA')">
    <x-page-header :title="__('Google reCAPTCHA Setting')" />

    <div class="grid max-w-3xl gap-6">
        <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card">
            @csrf
            <div class="card-body space-y-3">
                <x-setting key="google_recaptcha" as="check" :label="__('Google reCAPTCHA (v3) on checkout and reviews')" />
                <p class="form-hint">{{ __('Turn it on only after saving both keys below. Create keys at google.com/recaptcha/admin (type: Score based v3) and add your domain.') }}</p>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
        </form>
        <form method="POST" action="{{ route('admin.env_key_update') }}" class="card">
            @csrf
            <div class="card-body grid gap-4 md:grid-cols-2">
                <x-env-setting key="RECAPTCHA_SITE_KEY" :label="__('Site Key')" />
                <x-env-setting key="RECAPTCHA_SECRET_KEY" :label="__('Secret Key')" secret />
            </div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save keys') }}</button></div>
        </form>
    </div>
</x-layouts.admin>
