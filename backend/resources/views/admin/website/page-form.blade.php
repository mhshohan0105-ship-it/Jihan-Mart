@php($editing = $page->exists)
<x-layouts.admin :title="$editing ? __('Edit Page') : __('Add New Page')">
    <x-page-header :title="$editing ? __('Edit Page').': '.$page->title : __('Add New Page')">
        <a href="{{ route('admin.pages.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif
        <section class="card lg:col-span-2">
            <div class="card-body space-y-5">
                <x-form-errors />
                <div>
                    <label class="form-label" for="title">{{ __('Title') }} *</label>
                    <input id="title" name="title" value="{{ old('title', $page->title) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label" for="slug">{{ __('Link') }} *</label>
                    <div class="flex items-center overflow-hidden rounded-xl border border-slate-200">
                        <span class="bg-slate-50 px-3 py-2.5 text-sm text-slate-500">{{ url('/page') }}/</span>
                        <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" @disabled($page->type === 'default') class="flex-1 border-0 px-3 py-2.5 text-sm focus:ring-0 disabled:bg-slate-50">
                    </div>
                </div>
                <div>
                    <label class="form-label">{{ __('Add Content') }} *</label>
                    <div class="rounded-xl border border-slate-200" x-data="richText()">
                        <textarea x-ref="input" name="content" class="hidden">{{ old('content', $page->content) }}</textarea>
                        <div x-ref="editor" class="min-h-72 rounded-b-xl bg-white"></div>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="position">{{ __('Page Link Position') }}</label>
                    <select id="position" name="position" class="form-input">
                        <option value="">{{ __('Not in footer') }}</option>
                        @foreach (\App\Models\Page::POSITIONS as $key => $label)
                            <option value="{{ $key }}" @selected(old('position', $page->position) === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>
        <section class="card">
            <div class="card-header"><h2 class="card-title">{{ __('SEO Fields') }}</h2></div>
            <div class="card-body space-y-4">
                <div><label class="form-label" for="meta_title">{{ __('Meta Title') }}</label><input id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="form-input"></div>
                <div><label class="form-label" for="meta_description">{{ __('Meta Description') }}</label><textarea id="meta_description" name="meta_description" rows="3" class="form-input">{{ old('meta_description', $page->meta_description) }}</textarea></div>
                <div><label class="form-label" for="keywords">{{ __('Keywords') }}</label><input id="keywords" name="keywords" value="{{ old('keywords', $page->keywords) }}" class="form-input"></div>
                <x-upload-field name="meta_image_upload_id" :value="old('meta_image_upload_id', $page->meta_image_upload_id)" :label="__('Meta Image')" />
            </div>
        </section>
        <div class="flex justify-end lg:col-span-3"><button class="btn-primary">{{ __('Save Page') }}</button></div>
    </form>
</x-layouts.admin>
