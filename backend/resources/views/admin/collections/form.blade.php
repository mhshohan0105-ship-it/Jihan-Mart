@php($editing = $collection->exists)
<x-layouts.admin :title="$editing ? __('Edit Collection') : __('Collection Information')">
    <x-page-header :title="$editing ? __('Edit Collection') : __('Collection Information')">
        <a href="{{ route('admin.collections.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.collections.update', $collection) : route('admin.collections.store') }}" class="card max-w-2xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body space-y-5">
            <x-form-errors />
            <div>
                <label class="form-label" for="name">{{ __('Name') }} *</label>
                <input id="name" name="name" value="{{ old('name', $collection->name) }}" required class="form-input" placeholder="Free Delivery, Up To 50% Off...">
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
                    <input id="start_date" name="start_date" value="{{ old('start_date', $collection->start_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time class="form-input">
                </div>
                <div>
                    <label class="form-label" for="end_date">{{ __('End Date') }}</label>
                    <input id="end_date" name="end_date" value="{{ old('end_date', $collection->end_date?->format('Y-m-d H:i')) }}" x-data="datePicker()" data-time class="form-input">
                </div>
            </div>
            <x-upload-field name="image_upload_id" :value="old('image_upload_id', $collection->image_upload_id)" :label="__('Image')" hint="416x182" />
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="status" value="1" @checked(old('status', $collection->status)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                {{ __('Active') }}
            </label>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
