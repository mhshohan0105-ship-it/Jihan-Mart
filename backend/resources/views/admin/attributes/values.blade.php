<x-layouts.admin :title="$attribute->name">
    <x-page-header :title="__('Attribute values').': '.$attribute->name" :count="$attribute->values->count()">
        <a href="{{ route('admin.attributes.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Value') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attribute->values as $value)
                        <tr x-data="{ editing: false }">
                            <td class="px-5 py-3 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3">
                                <span x-show="!editing" class="font-medium">{{ $value->value }}</span>
                                <form x-cloak x-show="editing" method="POST" action="{{ route('admin.attributes.values.update', $value) }}" class="flex gap-2">
                                    @csrf @method('PUT')
                                    <input name="value" value="{{ $value->value }}" class="form-input" required>
                                    <button class="btn-primary btn-sm">{{ __('Save') }}</button>
                                </form>
                            </td>
                            <td class="px-5 py-3">
                                @can('edit_attributes')
                                    <div class="flex justify-end gap-1">
                                        <button type="button" @click="editing = !editing" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></button>
                                        <button type="button" @click="confirmDelete(@js(route('admin.attributes.values.destroy', $value)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('edit_attributes')
            <form method="POST" action="{{ route('admin.attributes.values.store', $attribute) }}" class="card h-fit">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Add New Attribute Value') }}</h2></div>
                <div class="card-body space-y-4">
                    <x-form-errors />
                    <div>
                        <label class="form-label" for="value">{{ __('Value') }} *</label>
                        <input id="value" name="value" required class="form-input" placeholder="XL, 3 Pcs, Rose Red...">
                    </div>
                    <div class="text-right"><button class="btn-primary">{{ __('Save') }}</button></div>
                </div>
            </form>
        @endcan
    </div>
</x-layouts.admin>
