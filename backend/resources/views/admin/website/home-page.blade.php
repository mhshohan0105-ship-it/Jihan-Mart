<x-layouts.admin :title="__('Home Page Settings')">
    <x-page-header :title="__('Home Page Settings')">
        <a href="{{ route('admin.pages.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card max-w-4xl">
        @csrf
        <div class="card-body space-y-6">
            <div>
                <input type="hidden" name="types[]" value="home_slider">
                <x-repeater name="home_slider" :label="__('Home Slider')" hint="1296x331" :rows="get_setting('home_slider')" />
                <p class="form-hint">{{ __('Shown below the hero section. Leave empty to hide.') }}</p>
            </div>
            @foreach (['home_banner1' => 'Home Banner Section 1', 'home_banner2' => 'Home Banner Section 2', 'home_banner3' => 'Home Banner Section 3'] as $key => $label)
                <div>
                    <input type="hidden" name="types[]" value="{{ $key }}">
                    <x-repeater :name="$key" :label="__($label)" :rows="get_setting($key)" :max="3" />
                </div>
            @endforeach
            <div>
                <input type="hidden" name="types[]" value="home_categories">
                <label class="form-label" for="home_categories">{{ __('Home Categories') }}</label>
                @php($picked = json_decode((string) get_setting('home_categories'), true) ?: [])
                <select id="home_categories" name="home_categories[]" multiple x-data="tomSelect()" placeholder="{{ __('All categories') }}">
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(in_array($c->id, array_map('intval', $picked), true))>{{ $c->name }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('Order and limit the category cards on the home page. Empty shows every category.') }}</p>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Update') }}</button></div>
    </form>
</x-layouts.admin>
