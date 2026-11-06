<x-layouts.admin :title="__('Appearance')">
    <x-page-header :title="__('Website Appearance')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2">
        @csrf
        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('General') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="site_name" :label="__('Frontend Website Name')" default="Jihan Mart" />
                <x-setting key="site_motto" :label="__('Site Motto')" default="সাশ্রয়ী দামে সেরা পণ্য" />
                <x-setting key="site_icon" as="image" :label="__('Site Icon')" hint="32x32" />
                <div class="grid grid-cols-2 gap-4">
                    <x-setting key="base_color" as="color" :label="__('Website Base Color')" default="#f47c20" />
                    <x-setting key="base_hover_color" as="color" :label="__('Website Base Hover Color')" default="#d9661a" />
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Global SEO') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="meta_title" :label="__('Meta Title')" />
                <x-setting key="meta_description" as="textarea" :label="__('Meta description')" />
                <x-setting key="meta_keywords" :label="__('Keywords')" :placeholder="__('Separate with comma')" />
                <x-setting key="meta_image" as="image" :label="__('Meta Image')" hint="1200x630" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Cookies Agreement') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="show_cookies_agreement" as="check" :label="__('Show Cookies Agreement?')" />
                <x-setting key="cookies_agreement_text" as="textarea" :label="__('Cookies Agreement Text')" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Website Popup') }}</h2></div>
            <div class="card-body space-y-4">
                <x-setting key="show_website_popup" as="check" :label="__('Show website popup?')" />
                <x-setting key="website_popup_content" as="rich" :label="__('Popup content')" />
                <x-setting key="show_subscribe_form" as="check" :label="__('Show subscriber form in popup?')" />
            </div>
        </section>

        <section class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Custom Script') }}</h2></div>
            <div class="card-body grid gap-4 md:grid-cols-2">
                <div>
                    <x-setting key="header_script" as="code" :label="__('Header custom script')" placeholder="<script>...</script>" />
                    <p class="form-hint">{{ __('Placed before </head>. Facebook Pixel, Google Analytics, etc.') }}</p>
                </div>
                <div>
                    <x-setting key="footer_script" as="code" :label="__('Footer custom script')" placeholder="<script>...</script>" />
                    <p class="form-hint">{{ __('Placed before </body>.') }}</p>
                </div>
            </div>
        </section>

        <div class="flex justify-end lg:col-span-2"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
