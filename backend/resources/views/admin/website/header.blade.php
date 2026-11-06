<x-layouts.admin :title="__('Header')">
    <x-page-header :title="__('Website Header')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card max-w-3xl">
        @csrf
        <div class="card-body space-y-5">
            <x-setting key="header_logo" as="image" :label="__('Header Logo')" hint="244x40" />
            <div class="grid gap-3 md:grid-cols-2">
                <x-setting key="show_language_switcher" as="check" :label="__('Show Language Switcher')" :default="true" />
                <x-setting key="enable_sticky_header" as="check" :label="__('Enable sticky header')" :default="true" />
            </div>
            <x-setting key="helpline_number" :label="__('Help line number')" placeholder="01XXXXXXXXX" />

            <div class="rounded-2xl border border-slate-100 p-4 space-y-4">
                <p class="text-sm font-semibold text-slate-700">{{ __('Topbar') }}</p>
                <div>
                    <input type="hidden" name="types[]" value="topbar_announcements">
                    <x-repeater name="topbar_announcements" :label="__('Scrolling announcements')" :rows="get_setting('topbar_announcements')"
                                :fields="['text' => ['text', '🚚 ৳২০০০+ অর্ডারে ফ্রি ডেলিভারি']]" />
                    <p class="form-hint">{{ __('Leave empty to keep the default lines.') }}</p>
                </div>
                <x-setting key="topbar_banner" as="image" :label="__('Topbar Banner')" hint="1920x40" />
                <x-setting key="topbar_banner_link" :label="__('Topbar Banner Link')" placeholder="https://" />
            </div>

            <div>
                <input type="hidden" name="types[]" value="header_menu">
                <x-repeater name="header_menu" :label="__('Header Nav Menu')" :rows="get_setting('header_menu')"
                            :fields="['label' => ['text', __('Label')], 'link' => ['text', '/page/return-policy or #shop']]" />
                <p class="form-hint">{{ __('Leave empty to keep the default menu (Home, Categories, Hot offers, Shop, Mystery box, Help).') }}</p>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
