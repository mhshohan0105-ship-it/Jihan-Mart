<x-layouts.admin :title="__('Staff permissions')">
    <x-page-header :title="__('All Roles')" :count="$roles->count()">
        <a href="{{ route('admin.roles.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add New Role') }}</a>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Permissions') }}</th><th class="px-5 py-3">{{ __('Staffs') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($roles as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3 font-medium">{{ $row->name }}</td>
                        <td class="px-5 py-3">{{ $row->permissions_count }}</td>
                        <td class="px-5 py-3">{{ $row->users_count }}</td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.roles.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>
                                <button type="button" @click="confirmDelete(@js(route('admin.roles.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
