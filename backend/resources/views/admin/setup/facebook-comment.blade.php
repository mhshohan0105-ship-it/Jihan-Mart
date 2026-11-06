<x-layouts.admin :title="__('Facebook Comment')">
    <x-page-header :title="__('Facebook Comment Setting')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card max-w-2xl">
        @csrf
        <div class="card-body space-y-4">
            <x-setting key="facebook_comment" as="check" :label="__('Facebook Comment')" />
            <x-setting key="facebook_comment_app_id" :label="__('Facebook App ID')" />
            <p class="form-hint">{{ __('Shows the Facebook comment box on custom pages (Website Setup → Pages).') }}</p>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
