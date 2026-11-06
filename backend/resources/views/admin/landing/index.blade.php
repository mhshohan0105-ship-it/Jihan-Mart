<x-layouts.admin :title="__('Landing Home Pages')">
    <x-page-header :title="__('Landing Home Pages')" :count="$pages->total()">
        @can('add_landing_pages')
            <a href="{{ route('admin.landing_pages.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Create') }}</a>
        @endcan
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">SL</th><th class="px-5 py-3">{{ __('Domain') }}</th><th class="px-5 py-3">{{ __('Products') }}</th><th class="px-5 py-3">{{ __('Orders') }}</th><th class="px-5 py-3">{{ __('Status') }}</th><th class="px-5 py-3 text-right">{{ __('Action') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pages as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $pages->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-3 font-medium"><a href="http://{{ $row->domain }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">{{ $row->domain }}</a></td>
                        <td class="px-5 py-3">{{ count($row->product_ids ?? []) }}</td>
                        <td class="px-5 py-3">
                            @can('view_landing_orders')<a href="{{ route('admin.landing_orders', ['landing_domain' => $row->domain]) }}" class="hover:underline">{{ $orders[$row->domain] ?? 0 }}</a>@else {{ $orders[$row->domain] ?? 0 }} @endcan
                        </td>
                        <td class="px-5 py-3"><x-switch :url="route('admin.toggle', ['landing_pages', $row->id])" field="status" :checked="$row->status" :disabled="! auth()->user()->can('edit_landing_pages')" /></td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                @can('edit_landing_pages')<a href="{{ route('admin.landing_pages.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                @can('delete_landing_pages')<button type="button" @click="confirmDelete(@js(route('admin.landing_pages.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="card-body">{{ $pages->links() }}</div>
    </div>
    <p class="form-hint mt-3">{{ __('Point the domain (A record) at this server. When a visitor opens that domain the landing page shows instead of the main store, and its orders are marked with the domain.') }}</p>
</x-layouts.admin>
