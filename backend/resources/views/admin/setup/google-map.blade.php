<x-layouts.admin :title="__('Google Map')">
    <x-page-header :title="__('Google Map Setting')" />

    <div class="grid max-w-3xl gap-6">
        <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card">
            @csrf
            <div class="card-body space-y-3">
                <x-setting key="google_map" as="check" :label="__('Show the shop location map in the storefront Help section')" />
                <x-setting key="google_map_query" :label="__('Map location')" :placeholder="get_setting('contact_address')" />
                <p class="form-hint">{{ __('Empty uses the contact address. The simple embed works without a key; the key is only needed for the Maps JavaScript API.') }}</p>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
        </form>
        <form method="POST" action="{{ route('admin.env_key_update') }}" class="card">
            @csrf
            <div class="card-body"><x-env-setting key="GOOGLE_MAP_KEY" :label="__('Google Map API Key')" secret /></div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save key') }}</button></div>
        </form>
    </div>
</x-layouts.admin>
