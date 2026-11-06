<x-layouts.admin :title="__('Social media Logins')">
    <x-page-header :title="__('Social Media Logins')" />

    <div class="mb-4 max-w-5xl rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
        {{ __('The storefront checks out as a guest (no customer login yet). These keys are kept ready for when customer accounts are added.') }}
    </div>
    <form method="POST" action="{{ route('admin.env_key_update') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2">
        @csrf
        <section class="card">
            <div class="card-header"><h2 class="card-title">Google</h2></div>
            <div class="card-body space-y-4">
                <x-env-setting key="GOOGLE_CLIENT_ID" label="Client ID" />
                <x-env-setting key="GOOGLE_CLIENT_SECRET" label="Client Secret" secret />
            </div>
        </section>
        <section class="card">
            <div class="card-header"><h2 class="card-title">Facebook</h2></div>
            <div class="card-body space-y-4">
                <x-env-setting key="FACEBOOK_CLIENT_ID" label="App ID" />
                <x-env-setting key="FACEBOOK_CLIENT_SECRET" label="App Secret" secret />
            </div>
        </section>
        <div class="flex justify-end lg:col-span-2"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
