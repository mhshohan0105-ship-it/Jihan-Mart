<x-layouts.admin :title="__('Collections')">
    <x-page-header :title="__('All Collection')" :count="$collections->total()">
        @can('add_collections')
            <a href="{{ route('admin.collections.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add New') }}</a>
        @endcan
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Start Date') }}</th>
                    <th class="px-5 py-3">{{ __('End Date') }}</th><th class="px-5 py-3">{{ __('Image') }}</th><th class="px-5 py-3">{{ __('Products') }}</th>
                    <th class="px-5 py-3">{{ __('Status') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($collections as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $collections->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-3 font-medium">{{ $row->name }}</td>
                        <td class="px-5 py-3">{{ $row->start_date ? format_date($row->start_date) : '—' }}</td>
                        <td class="px-5 py-3">{{ $row->end_date ? format_date($row->end_date) : '—' }}</td>
                        <td class="px-5 py-3">@if ($img = uploaded_asset($row->image_upload_id))<img src="{{ $img }}" alt="" class="h-10 w-24 rounded-lg object-cover">@else — @endif</td>
                        <td class="px-5 py-3">{{ $row->products_count }}</td>
                        <td class="px-5 py-3"><x-switch :url="route('admin.toggle', ['collections', $row->id])" field="status" :checked="$row->status" :disabled="! auth()->user()->can('edit_collections')" /></td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                @can('edit_collections')<a href="{{ route('admin.collections.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                @can('delete_collections')<button type="button" @click="confirmDelete(@js(route('admin.collections.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="card-body">{{ $collections->links() }}</div>
    </div>
    <p class="form-hint mt-3">{{ __('Add products to a collection from the All Products page (Collection column).') }}</p>
</x-layouts.admin>
