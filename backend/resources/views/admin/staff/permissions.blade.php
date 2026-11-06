<x-layouts.admin :title="__('Permissions')">
    <x-page-header :title="__('All Permissions')" :count="$permissions->count()" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Permissions') }}</th><th class="px-5 py-3">{{ __('Section') }}</th><th class="px-5 py-3">{{ __('Guard Name') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($permissions as $row)
                    <tr>
                        <td class="px-5 py-2 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-2 font-mono text-xs">{{ $row->name }}</td>
                        <td class="px-5 py-2">{{ __($row->section) }}</td>
                        <td class="px-5 py-2">{{ $row->guard_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="form-hint mt-3">{{ __('Permissions are defined in config/admin_permissions.php and synced by the PermissionSeeder; assign them to roles from Staff permissions.') }}</p>
</x-layouts.admin>
