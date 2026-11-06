@php($editing = $attribute->exists)
<x-layouts.admin :title="__('Attributes')">
    <x-page-header :title="__('All Attributes')" :count="$attributes->total()" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Attributes') }}</h2></div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Values') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attributes as $row)
                        <tr>
                            <td class="px-5 py-3 text-slate-500">{{ $attributes->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($row->values->take(12) as $value)
                                        <span class="badge bg-slate-100 text-slate-700">{{ $value->value }}</span>
                                    @empty
                                        <span class="text-slate-400">—</span>
                                    @endforelse
                                    @if ($row->values->count() > 12)<span class="badge bg-slate-100 text-slate-500">+{{ $row->values->count() - 12 }}</span>@endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.attributes.show', $row) }}" class="btn-light btn-sm">{{ __('Attribute values') }}</a>
                                    @can('edit_attributes')<a href="{{ route('admin.attributes.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                    @can('delete_attributes')<button type="button" @click="confirmDelete(@js(route('admin.attributes.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="card-body">{{ $attributes->links() }}</div>
        </div>

        @canany(['add_attributes', 'edit_attributes'])
            <form method="POST" action="{{ $editing ? route('admin.attributes.update', $attribute) : route('admin.attributes.store') }}" class="card h-fit">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="card-header"><h2 class="card-title">{{ $editing ? __('Edit Attribute') : __('Add New Attribute') }}</h2></div>
                <div class="card-body space-y-4">
                    <x-form-errors />
                    <div>
                        <label class="form-label" for="name">{{ __('Name') }} *</label>
                        <input id="name" name="name" value="{{ old('name', $attribute->name) }}" required class="form-input" placeholder="Size, Shade, Pcs...">
                    </div>
                    <div class="flex justify-end gap-2">
                        @if ($editing)<a href="{{ route('admin.attributes.index') }}" class="btn-light">{{ __('Cancel') }}</a>@endif
                        <button class="btn-primary">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        @endcanany
    </div>
</x-layouts.admin>
