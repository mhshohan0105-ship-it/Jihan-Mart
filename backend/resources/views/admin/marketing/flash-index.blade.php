<x-layouts.admin :title="__('Flash Deals')">
    <x-page-header :title="__('All Flash Deals')" :count="$deals->total()">
        @can('add_flash_deals')
            <a href="{{ route('admin.flash_deals.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Create New Flash Deal') }}</a>
        @endcan
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Title') }}</th><th class="px-5 py-3">{{ __('Banner') }}</th>
                    <th class="px-5 py-3">{{ __('Start Date') }}</th><th class="px-5 py-3">{{ __('End Date') }}</th><th class="px-5 py-3">{{ __('Products') }}</th>
                    <th class="px-5 py-3">{{ __('Status') }}</th><th class="px-5 py-3">{{ __('Featured') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($deals as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $deals->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-3 font-medium">
                            {{ $row->title }}
                            @if ($row->isRunning())<span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">{{ __('Running') }}</span>@endif
                        </td>
                        <td class="px-5 py-3">@if ($img = uploaded_asset($row->banner_upload_id))<img src="{{ $img }}" alt="" class="h-10 w-24 rounded-lg object-cover">@else — @endif</td>
                        <td class="px-5 py-3">{{ format_date($row->start_date) }}</td>
                        <td class="px-5 py-3">{{ format_date($row->end_date) }}</td>
                        <td class="px-5 py-3">{{ $row->items_count }}</td>
                        <td class="px-5 py-3"><x-switch :url="route('admin.toggle', ['flash_deals', $row->id])" field="status" :checked="$row->status" :disabled="! auth()->user()->can('edit_flash_deals')" /></td>
                        <td class="px-5 py-3"><x-switch :url="route('admin.toggle', ['flash_deals', $row->id])" field="featured" :checked="$row->featured" :disabled="! auth()->user()->can('edit_flash_deals')" /></td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                @can('edit_flash_deals')<a href="{{ route('admin.flash_deals.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                @can('delete_flash_deals')<button type="button" @click="confirmDelete(@js(route('admin.flash_deals.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="card-body">{{ $deals->links() }}</div>
    </div>
    <p class="form-hint mt-3">{{ __('Only one featured deal shows on the home page. A running deal replaces the product discount.') }}</p>
</x-layouts.admin>
