<x-layouts.admin :title="__('Subscribers')">
    <x-page-header :title="__('All Subscribers')" :count="$subscribers->total()">
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" class="form-input w-56" placeholder="{{ __('Email or phone') }}">
            <button class="btn-light">{{ __('Search') }}</button>
        </form>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Email') }}</th><th class="px-5 py-3">{{ __('Phone') }}</th><th class="px-5 py-3">{{ __('Date') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($subscribers as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $subscribers->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-3">{{ $row->email ?? '—' }}</td>
                        <td class="px-5 py-3">{{ $row->phone ?? '—' }}</td>
                        <td class="px-5 py-3">{{ format_date($row->created_at) }}</td>
                        <td class="px-5 py-3 text-right">
                            @can('delete_subscribers')<button type="button" @click="confirmDelete(@js(route('admin.subscribers.destroy', $row)))" class="inline-grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="card-body">{{ $subscribers->links() }}</div>
    </div>
</x-layouts.admin>
