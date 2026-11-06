@php($editing = $brand->exists)
<x-layouts.admin :title="__('Brands')">
    <x-page-header :title="__('All Brands')" :count="$brands->total()" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form method="GET" class="card-header">
                <h2 class="card-title">{{ __('Brands') }}</h2>
                <input type="search" name="search" value="{{ request('search') }}" class="form-input w-56" placeholder="{{ __('Type name & Enter') }}">
            </form>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Logo') }}</th><th class="px-5 py-3">{{ __('Products') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($brands as $row)
                        <tr class="{{ $editing && $row->id === $brand->id ? 'bg-brand-50/50' : '' }}">
                            <td class="px-5 py-3 text-slate-500">{{ $brands->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-5 py-3">
                                @if ($logo = uploaded_asset($row->logo_upload_id))<img src="{{ $logo }}" alt="" class="h-8 w-14 object-contain">@else — @endif
                            </td>
                            <td class="px-5 py-3">{{ $row->products_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    @can('edit_brands')<a href="{{ route('admin.brands.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                    @can('delete_brands')<button type="button" @click="confirmDelete(@js(route('admin.brands.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="card-body">{{ $brands->links() }}</div>
        </div>

        @canany(['add_brands', 'edit_brands'])
            <form method="POST" action="{{ $editing ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" class="card h-fit">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="card-header"><h2 class="card-title">{{ $editing ? __('Edit Brand') : __('Add New Brand') }}</h2></div>
                <div class="card-body space-y-4">
                    <x-form-errors />
                    <div>
                        <label class="form-label" for="name">{{ __('Name') }} *</label>
                        <input id="name" name="name" value="{{ old('name', $brand->name) }}" required class="form-input">
                    </div>
                    <x-upload-field name="logo_upload_id" :value="old('logo_upload_id', $brand->logo_upload_id)" :label="__('Logo')" hint="120x80" />
                    <div>
                        <label class="form-label" for="meta_title">{{ __('Meta Title') }}</label>
                        <input id="meta_title" name="meta_title" value="{{ old('meta_title', $brand->meta_title) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="meta_description">{{ __('Meta Description') }}</label>
                        <textarea id="meta_description" name="meta_description" rows="3" class="form-input">{{ old('meta_description', $brand->meta_description) }}</textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        @if ($editing)<a href="{{ route('admin.brands.index') }}" class="btn-light">{{ __('Cancel') }}</a>@endif
                        <button class="btn-primary">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        @endcanany
    </div>
</x-layouts.admin>
