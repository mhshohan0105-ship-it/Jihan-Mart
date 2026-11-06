@php($editing = $category->exists)
<x-layouts.admin :title="$editing ? __('Edit Category') : __('Add New category')">
    <x-page-header :title="$editing ? __('Edit Category') : __('Add New category')">
        <a href="{{ route('admin.categories.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body space-y-5">
            <x-form-errors />
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="name">{{ __('Name') }} *</label>
                    <input id="name" name="name" value="{{ old('name', $category->name) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label" for="name_bn">{{ __('Bangla name') }}</label>
                    <input id="name_bn" name="name_bn" value="{{ old('name_bn', $category->name_bn) }}" class="form-input" placeholder="কসমেটিক ও বিউটি">
                </div>
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="form-label" for="parent_id">{{ __('Parent Category') }}</label>
                    <select id="parent_id" name="parent_id" x-data="tomSelect()">
                        <option value="">{{ __('No Parent') }}</option>
                        @foreach ($tree as $node)
                            <option value="{{ $node->id }}" @selected(old('parent_id', $category->parent_id) == $node->id)>{{ str_repeat('-- ', $node->depth) }}{{ $node->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="order_level">{{ __('Ordering Number') }}</label>
                    <input id="order_level" name="order_level" type="number" value="{{ old('order_level', $category->order_level ?? 0) }}" class="form-input">
                    <p class="form-hint">{{ __('Higher number shows first.') }}</p>
                </div>
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                <x-upload-field name="banner_upload_id" :value="old('banner_upload_id', $category->banner_upload_id)" :label="__('Banner')" hint="200x200" />
                <x-upload-field name="image_upload_id" :value="old('image_upload_id', $category->image_upload_id)" :label="__('Image')" hint="306x306" />
                <div>
                    <label class="form-label" for="icon">{{ __('Icon (emoji)') }}</label>
                    <input id="icon" name="icon" value="{{ old('icon', $category->icon) }}" maxlength="16" class="form-input" placeholder="💄">
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-4">
                @foreach (['featured' => 'Featured', 'single_grid' => 'Single Grid', 'megamenu' => 'Mega menu'] as $flag => $label)
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, $category->$flag)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                        {{ __($label) }}
                    </label>
                @endforeach
                <div>
                    <label class="form-label" for="mega_section">{{ __('Mega Section') }}</label>
                    <select id="mega_section" name="mega_section" class="form-input">
                        @foreach (range(1, 4) as $n)
                            <option value="{{ $n }}" @selected(old('mega_section', $category->mega_section) == $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="meta_title">{{ __('Meta Title') }}</label>
                    <input id="meta_title" name="meta_title" value="{{ old('meta_title', $category->meta_title) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="meta_description">{{ __('Meta Description') }}</label>
                    <textarea id="meta_description" name="meta_description" rows="2" class="form-input">{{ old('meta_description', $category->meta_description) }}</textarea>
                </div>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4">
            <button class="btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
</x-layouts.admin>
