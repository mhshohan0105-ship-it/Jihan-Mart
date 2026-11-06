<x-layouts.admin :title="__('All staffs')">
    <x-page-header :title="__('All staffs')" :count="$staffs->total()">
        @can('add_staffs')
            <a href="{{ route('admin.staffs.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add New Staffs') }}</a>
        @endcan
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Email') }}</th><th class="px-5 py-3">{{ __('Phone') }}</th><th class="px-5 py-3">{{ __('Role') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($staffs as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $staffs->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-3 font-medium">{{ $row->name }} @if ($row->banned)<span class="badge bg-red-100 text-red-700">{{ __('Banned') }}</span>@endif</td>
                        <td class="px-5 py-3">{{ $row->email }}</td>
                        <td class="px-5 py-3">{{ $row->phone ?? '—' }}</td>
                        <td class="px-5 py-3">{{ $row->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                @can('edit_staffs')<a href="{{ route('admin.staffs.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                @can('delete_staffs')<button type="button" @click="confirmDelete(@js(route('admin.staffs.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="card-body">{{ $staffs->links() }}</div>
    </div>
    <p class="form-hint mt-3">{{ __('The main admin account (super admin) is not listed and always has every permission.') }}</p>
</x-layouts.admin>
