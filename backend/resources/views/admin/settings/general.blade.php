<x-layouts.admin :title="__('General Settings')">
    <h1 class="mb-6 text-2xl font-bold">{{ __('General Settings') }}</h1>

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card max-w-3xl">
        @csrf
        <div class="card-header"><h2 class="card-title">{{ __('General Settings') }}</h2></div>
        <div class="card-body space-y-5">
            <div>
                <input type="hidden" name="types[]" value="system_name">
                <label class="form-label" for="system_name">{{ __('System Name') }}</label>
                <input id="system_name" name="system_name" value="{{ get_setting('system_name') }}" class="form-input">
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <input type="hidden" name="types[]" value="system_logo_white">
                    <x-upload-field name="system_logo_white" :value="get_setting('system_logo_white')" :label="__('System Logo - White')" hint="160x40" />
                    <p class="form-hint">{{ __('Shown on the dark sidebar.') }}</p>
                </div>
                <div>
                    <input type="hidden" name="types[]" value="system_logo_black">
                    <x-upload-field name="system_logo_black" :value="get_setting('system_logo_black')" :label="__('System Logo - Black')" hint="160x40" />
                    <p class="form-hint">{{ __('Shown on the login page and invoices.') }}</p>
                </div>
            </div>

            <div>
                <input type="hidden" name="types[]" value="system_timezone">
                <label class="form-label" for="system_timezone">{{ __('System Timezone') }}</label>
                <select id="system_timezone" name="system_timezone" class="form-input">
                    @foreach ($timezones as $tz)
                        <option value="{{ $tz }}" @selected(get_setting('system_timezone', 'Asia/Dhaka') === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <input type="hidden" name="types[]" value="admin_login_background">
                <x-upload-field name="admin_login_background" :value="get_setting('admin_login_background')" :label="__('Admin login page background')" hint="1920x1080" />
            </div>

            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                <p class="mb-4 text-sm font-semibold text-slate-700">{{ __('Contact & social (shown on the website footer and invoices)') }}</p>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach (['contact_phone' => 'Phone', 'contact_email' => 'Email', 'contact_address' => 'Address', 'whatsapp_number' => 'WhatsApp number',
                        'facebook_link' => 'Facebook page link', 'instagram_link' => 'Instagram link', 'tiktok_link' => 'TikTok link'] as $type => $label)
                        <div>
                            <input type="hidden" name="types[]" value="{{ $type }}">
                            <label class="form-label" for="{{ $type }}">{{ __($label) }}</label>
                            <input id="{{ $type }}" name="{{ $type }}" value="{{ get_setting($type) }}" class="form-input">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                <p class="mb-4 text-sm font-semibold text-slate-700">{{ __('External POS sync (optional)') }}</p>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach (['pos_api_url' => 'POS API URL', 'pos_api_token' => 'POS API Token', 'pos_business_location_id' => 'Business Location ID'] as $type => $label)
                        <div>
                            <input type="hidden" name="types[]" value="{{ $type }}">
                            <label class="form-label" for="{{ $type }}">{{ __($label) }}</label>
                            <input id="{{ $type }}" name="{{ $type }}" value="{{ get_setting($type) }}" class="form-input" @if ($type === 'pos_api_token') type="password" autocomplete="off" @endif>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4">
            <button class="btn-primary">{{ __('Update') }}</button>
        </div>
    </form>
</x-layouts.admin>
