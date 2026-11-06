<x-layouts.admin :title="__('Facebook Chat')">
    <x-page-header :title="__('Facebook Chat Setting')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card max-w-2xl">
        @csrf
        <div class="card-body space-y-4">
            <x-setting key="facebook_chat" as="check" :label="__('Facebook Chat')" />
            <x-setting key="facebook_page_id" :label="__('Facebook Page ID')" placeholder="1234567890" />
            <p class="form-hint">{{ __('Facebook Page → About → Page transparency → Page ID. Also add the website domain in Page settings → Messaging → Chat plugin.') }}</p>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
