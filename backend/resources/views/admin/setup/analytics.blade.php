<x-layouts.admin :title="__('Analytics Tools')">
    <x-page-header :title="__('Analytics Tools')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2">
        @csrf
        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Facebook Pixel Setting') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="facebook_pixel" as="check" :label="__('Facebook Pixel')" />
                <x-setting key="facebook_pixel_id" :label="__('Facebook Pixel ID')" placeholder="123456789012345" />
                <p class="form-hint">{{ __('Sends PageView, ViewContent (quick view), AddToCart, InitiateCheckout and Purchase from the storefront and landing pages.') }}</p>
            </div>
        </section>
        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Google Analytics Setting') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="google_analytics" as="check" :label="__('Google Analytics')" />
                <x-setting key="google_analytics_id" :label="__('Tracking ID')" placeholder="G-XXXXXXXXXX" />
            </div>
        </section>
        <div class="flex justify-end lg:col-span-2"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
