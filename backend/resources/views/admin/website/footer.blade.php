<x-layouts.admin :title="__('Footer')">
    <x-page-header :title="__('Website Footer')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2">
        @csrf
        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('About Widget') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="footer_logo" as="image" :label="__('Footer Logo')" />
                <x-setting key="about_us_description" as="textarea" :label="__('About description')" />
                <x-setting key="play_store_link" :label="__('Play Store Link')" placeholder="https://" />
                <x-setting key="app_store_link" :label="__('App Store Link')" placeholder="https://" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Contact Info Widget') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="contact_address" :label="__('Contact address')" />
                <x-setting key="contact_phone" :label="__('Contact phone')" />
                <x-setting key="contact_email" :label="__('Contact email')" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Link Widget One') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="widget_one_title" :label="__('Title')" :placeholder="__('Quick links')" />
                <div>
                    <input type="hidden" name="types[]" value="widget_one_links">
                    <x-repeater name="widget_one_links" :label="__('Links')" :rows="get_setting('widget_one_links')"
                                :fields="['label' => ['text', __('Label')], 'link' => ['text', '/page/terms']]" />
                </div>
                <p class="form-hint">{{ __('Pages with a "Page Link Position" are listed in the footer automatically.') }}</p>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Social Links') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="show_social_links" as="check" :label="__('Show Social Links')" :default="true" />
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach (['facebook_link' => 'Facebook', 'messenger_link' => 'Messenger', 'twitter_link' => 'Twitter', 'instagram_link' => 'Instagram',
                        'youtube_link' => 'YouTube', 'linkedin_link' => 'LinkedIn', 'tiktok_link' => 'TikTok', 'whatsapp_number' => 'WhatsApp number'] as $key => $label)
                        <x-setting :key="$key" :label="$label" />
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Copyright & payment') }}</h2></div>
            <div class="card-body grid gap-4 md:grid-cols-2">
                <x-setting key="frontend_copyright_text" as="textarea" :label="__('Copyright Text')" placeholder="Copyright © Jihan Mart" />
                <x-setting key="payment_method_images" as="images" :label="__('Payment Methods Widget')" />
            </div>
        </section>

        <div class="flex justify-end lg:col-span-2"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
