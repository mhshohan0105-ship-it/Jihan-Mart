@php
    $editing = $page->exists;
    $banners = old('banners', $page->banners ?? []);
    $contents = array_pad(old('contents', $page->contents ?? []), 3, ['title' => '', 'body' => '']);
    $picked = collect(old('product_ids', $page->product_ids ?? []))->map(fn ($v) => (int) $v);
@endphp
<x-layouts.admin :title="$editing ? __('Edit Landing Page') : __('Create Landing Page')">
    <x-page-header :title="$editing ? __('Edit Landing Page').': '.$page->domain : __('Create Landing Page')">
        <a href="{{ route('admin.landing_pages.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.landing_pages.update', $page) : route('admin.landing_pages.store') }}" class="max-w-4xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-form-errors />

        <section class="card">
            <div class="card-body space-y-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="domain">{{ __('Domain Name') }} *</label>
                        <input id="domain" name="domain" value="{{ old('domain', $page->domain) }}" required class="form-input" placeholder="offer.jihanmart.com">
                    </div>
                    <x-upload-field name="logo_upload_id" :value="old('logo_upload_id', $page->logo_upload_id)" :label="__('Logo')" />
                </div>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="status" value="1" @checked(old('status', $page->status)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"> {{ __('Active') }}
                </label>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Home Slider & Video') }}</h2></div>
            <div class="card-body space-y-5">
                <x-repeater name="sliders" :rows="old('sliders', $page->sliders ?? [])" :label="__('Home Slider')" />
                <div>
                    <label class="form-label" for="video_link">{{ __('Home Video 1') }}</label>
                    <input id="video_link" name="video_link" value="{{ old('video_link', $page->video_link) }}" class="form-input" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <x-repeater name="features" :rows="old('features', $page->features ?? [])" :label="__('Product features')" :fields="['image' => ['image', ''], 'text' => ['text', __('Feature text')]]" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Banners') }}</h2></div>
            <div class="card-body grid gap-5 md:grid-cols-2">
                @foreach (['banner1' => 'Home Banner 1', 'banner2' => 'Home Banner 2', 'banner3' => 'Home Banner 3', 'two' => 'Home Two Banner', 'three' => 'Home Three Banner'] as $key => $label)
                    <x-repeater :name="'banners['.$key.']'" :rows="$banners[$key] ?? []" :label="__($label)" :max="\App\Models\LandingPage::BANNERS[$key]" />
                @endforeach
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Home Products') }}</h2></div>
            <div class="card-body">
                <select name="product_ids[]" multiple x-data="tomSelect()" placeholder="{{ __('Search products') }}">
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected($picked->contains($p->id))>{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('These products appear with an order form on the landing page.') }}</p>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Home Content') }}</h2></div>
            <div class="card-body space-y-5">
                @foreach ($contents as $i => $c)
                    <div class="space-y-2">
                        <label class="form-label">{{ __('Home Content :n', ['n' => $i + 1]) }}</label>
                        <input name="contents[{{ $i }}][title]" value="{{ $c['title'] ?? '' }}" class="form-input" placeholder="{{ __('Title') }}">
                        <div class="rounded-xl border border-slate-200" x-data="richText()">
                            <textarea x-ref="input" name="contents[{{ $i }}][body]" class="hidden">{{ $c['body'] ?? '' }}</textarea>
                            <div x-ref="editor" class="min-h-32 rounded-b-xl bg-white"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('About Us & Contact') }}</h2></div>
            <div class="card-body space-y-5">
                <div class="rounded-xl border border-slate-200" x-data="richText()">
                    <textarea x-ref="input" name="about" class="hidden">{{ old('about', $page->about) }}</textarea>
                    <div x-ref="editor" class="min-h-32 rounded-b-xl bg-white"></div>
                </div>
                <div class="grid gap-5 md:grid-cols-3">
                    <div><label class="form-label" for="email">{{ __('Email') }}</label><input id="email" name="email" value="{{ old('email', $page->email) }}" class="form-input"></div>
                    <div><label class="form-label" for="phone">{{ __('Phone') }}</label><input id="phone" name="phone" value="{{ old('phone', $page->phone) }}" class="form-input"></div>
                    <div><label class="form-label" for="address">{{ __('Address') }}</label><input id="address" name="address" value="{{ old('address', $page->address) }}" class="form-input"></div>
                </div>
            </div>
        </section>

        <div class="flex justify-end"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
