<x-layouts.admin :title="__('Customers')">
<div x-data="{ selected: [] }">
    <x-page-header :title="__('Customers')" :count="$customers->total()">
        @can('delete_customers')
            <button type="button" class="btn-light" :disabled="!selected.length" @click="window.api(@js(route('admin.customers.bulk_destroy')), { method: 'DELETE', body: { ids: selected } }).then((r) => { toast(r.message); location.reload() }).catch((e) => toast(e.message, 'error'))">{{ __('Delete selection') }} <span x-show="selected.length" x-text="'(' + selected.length + ')'"></span></button>
        @endcan
    </x-page-header>
    <div class="card">
        <form method="GET" class="card-header">
            <h2 class="card-title">{{ __('All Customers') }}</h2>
            <input type="search" name="search" value="{{ request('search') }}" class="form-input w-64" placeholder="{{ __('Name, phone or email & Enter') }}">
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3"></th><th class="px-4 py-3">{{ __('Name') }}</th><th class="px-4 py-3">{{ __('Phone') }}</th><th class="px-4 py-3">{{ __('Email Address') }}</th><th class="px-4 py-3">{{ __('Orders') }}</th><th class="px-4 py-3">{{ __('Total spent') }}</th><th class="px-4 py-3">{{ __('Register At') }}</th><th class="px-4 py-3">{{ __('Banned') }}</th><th class="px-4 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $c)
                        <tr>
                            <td class="px-4 py-3"><input type="checkbox" value="{{ $c->id }}" x-model.number="selected" class="rounded border-slate-300 text-brand-500"></td>
                            <td class="px-4 py-3 font-medium"><a href="{{ route('admin.customers.show', $c) }}" class="hover:text-brand-600">{{ $c->name }}</a></td>
                            <td class="px-4 py-3">{{ $c->phone }}</td>
                            <td class="px-4 py-3">{{ $c->email ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $c->orders_count }}</td>
                            <td class="px-4 py-3">{{ format_price($c->orders_sum_grand_total) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs">{{ format_date($c->created_at) }}</td>
                            <td class="px-4 py-3"><x-switch :url="route('admin.customers.ban', $c)" :checked="$c->banned" :disabled="! auth()->user()->can('ban_customers')" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.customers.show', $c) }}" class="btn-light btn-sm">{{ __('View') }}</a>
                                    @can('delete_customers')<button type="button" @click="confirmDelete(@js(route('admin.customers.destroy', $c)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-16 text-center text-slate-500">{{ __('Customers are created automatically from orders (by phone number).') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $customers->links() }}</div>
    </div>
</div>
</x-layouts.admin>
