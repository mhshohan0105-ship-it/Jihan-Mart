<x-layouts.admin :title="__('Coupons')">
    <x-page-header :title="__('All Coupons')" :count="$coupons->total()">
        @can('add_coupons')
            <a href="{{ route('admin.coupons.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add New Coupon') }}</a>
        @endcan
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Code') }}</th><th class="px-5 py-3">{{ __('Type') }}</th>
                    <th class="px-5 py-3">{{ __('Discount') }}</th><th class="px-5 py-3">{{ __('Start Date') }}</th><th class="px-5 py-3">{{ __('End Date') }}</th>
                    <th class="px-5 py-3">{{ __('Used') }}</th><th class="px-5 py-3">{{ __('Status') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($coupons as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $coupons->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-3 font-mono font-semibold">{{ $row->code }}</td>
                        <td class="px-5 py-3">{{ __(\App\Models\Coupon::TYPES[$row->type] ?? $row->type) }}</td>
                        <td class="px-5 py-3">{{ $row->discount_type === 'percent' ? (float) $row->discount.'%' : format_price($row->discount) }}</td>
                        <td class="px-5 py-3">{{ $row->start_date ? format_date($row->start_date) : '—' }}</td>
                        <td class="px-5 py-3">{{ $row->end_date ? format_date($row->end_date) : '—' }}</td>
                        <td class="px-5 py-3">{{ $row->usages_count }}{{ $row->usage_limit ? ' / '.$row->usage_limit : '' }} <span class="text-xs text-slate-500">({{ format_price($row->usages_sum_amount ?? 0) }})</span></td>
                        <td class="px-5 py-3"><x-switch :url="route('admin.toggle', ['coupons', $row->id])" field="status" :checked="$row->status" :disabled="! auth()->user()->can('edit_coupons')" /></td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                @can('edit_coupons')<a href="{{ route('admin.coupons.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                @can('delete_coupons')<button type="button" @click="confirmDelete(@js(route('admin.coupons.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="card-body">{{ $coupons->links() }}</div>
    </div>
</x-layouts.admin>
