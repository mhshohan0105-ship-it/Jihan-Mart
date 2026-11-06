@php($editing = $color->exists)
<x-layouts.admin :title="__('Colors')">
    <x-page-header :title="__('All Colors')" :count="$colors->total()">
        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium">
            {{ __('Color filter activation') }}
            <x-switch :url="route('admin.colors.filter_activation')" :checked="setting_on('color_filter_activation')" :disabled="! auth()->user()->can('edit_colors')" />
        </div>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form method="GET" class="card-header">
                <h2 class="card-title">{{ __('Colors') }}</h2>
                <input type="search" name="search" value="{{ request('search') }}" class="form-input w-56" placeholder="{{ __('Type name & Enter') }}">
            </form>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($colors as $row)
                        <tr>
                            <td class="px-5 py-3 text-slate-500">{{ $colors->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3">
                                <span class="flex items-center gap-3 font-medium">
                                    <span class="size-6 rounded-full border border-slate-200" style="background: {{ $row->code }}"></span>
                                    {{ $row->name }} <span class="text-xs font-normal text-slate-400">{{ $row->code }}</span>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    @can('edit_colors')<a href="{{ route('admin.colors.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>@endcan
                                    @can('delete_colors')<button type="button" @click="confirmDelete(@js(route('admin.colors.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="card-body">{{ $colors->links() }}</div>
        </div>

        @canany(['add_colors', 'edit_colors'])
            <form method="POST" action="{{ $editing ? route('admin.colors.update', $color) : route('admin.colors.store') }}" class="card h-fit" x-data="{ code: @js(old('code', $color->code ?: '#f47c20')) }">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="card-header"><h2 class="card-title">{{ $editing ? __('Edit Color') : __('Add New Color') }}</h2></div>
                <div class="card-body space-y-4">
                    <x-form-errors />
                    <div>
                        <label class="form-label" for="name">{{ __('Name') }} *</label>
                        <input id="name" name="name" value="{{ old('name', $color->name) }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="code">{{ __('Color Code') }} *</label>
                        <div class="flex gap-2">
                            <input type="color" x-model="code" class="h-10 w-14 cursor-pointer rounded-lg border border-slate-200 p-1">
                            <input id="code" name="code" x-model="code" required pattern="#[0-9a-fA-F]{6}" class="form-input">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        @if ($editing)<a href="{{ route('admin.colors.index') }}" class="btn-light">{{ __('Cancel') }}</a>@endif
                        <button class="btn-primary">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        @endcanany
    </div>
</x-layouts.admin>
