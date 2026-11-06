<x-layouts.admin :title="__('Pos Configuration')">
    <x-page-header :title="__('POS Configuration')" />

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card max-w-3xl">
        @csrf
        <div class="card-header"><h2 class="card-title">{{ __('Invoice Contact Info') }}</h2></div>
        <div class="card-body space-y-4">
            <x-setting key="invoice_site_name" :label="__('Invoice Site Name')" :placeholder="get_setting('system_name', 'Jihan Mart')" />
            <x-setting key="invoice_address" :label="__('Contact address')" :placeholder="get_setting('contact_address')" />
            <div class="grid gap-4 md:grid-cols-2">
                <x-setting key="invoice_phone" :label="__('Contact phone')" :placeholder="get_setting('contact_phone')" />
                <x-setting key="invoice_email" :label="__('Contact email')" :placeholder="get_setting('contact_email')" />
            </div>
            <x-setting key="invoice_footer" as="textarea" :label="__('Invoice Footer')" placeholder="Thanks for shopping from Jihan Mart" />
            <p class="form-hint">{{ __('Empty fields use the contact info from General Settings. SKU / image on invoice: Features activation.') }}</p>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
