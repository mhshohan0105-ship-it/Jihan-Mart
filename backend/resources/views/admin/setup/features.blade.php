<x-layouts.admin :title="__('Features activation')">
    <x-page-header :title="__('Features activation')" />

    <div class="grid max-w-6xl gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($groups as $group => $features)
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __($group) }}</h2></div>
                <div class="card-body divide-y divide-slate-100">
                    @foreach ($features as $key => [$label, $default])
                        <div class="flex items-center justify-between gap-4 py-3">
                            <span class="text-sm font-medium">{{ __($label) }}</span>
                            <x-switch :url="route('admin.features.toggle')" :field="$key" :checked="setting_on($key, $default)" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    <p class="form-hint mt-4">{{ __('Maintenance Mode shows a "be right back" page to visitors; you stay logged in and can keep working. HTTPS Activation redirects every page to https (turn on only after SSL is installed). Turning a module off hides it from the sidebar.') }}</p>
</x-layouts.admin>
