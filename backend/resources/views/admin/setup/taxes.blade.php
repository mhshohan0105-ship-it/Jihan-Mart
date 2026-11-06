<x-layouts.admin :title="__('Vat & TAX')">
    <x-page-header :title="__('All Taxes')" :count="$taxes->count()" />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-3">
        <div class="card overflow-x-auto lg:col-span-2">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Tax Type') }}</th><th class="px-5 py-3">{{ __('Products') }}</th><th class="px-5 py-3">{{ __('Status') }}</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($taxes as $row)
                        <tr>
                            <td class="px-5 py-3 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-5 py-3">{{ $row->products_count }}</td>
                            <td class="px-5 py-3"><x-switch :url="route('admin.toggle', ['taxes', $row->id])" field="status" :checked="$row->status" /></td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.taxes.index', ['edit' => $row->id]) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>
                                    <button type="button" @click="confirmDelete(@js(route('admin.taxes.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ $editing ? route('admin.taxes.update', $editing) : route('admin.taxes.store') }}" class="card h-fit">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="card-header"><h2 class="card-title">{{ $editing ? __('Edit Tax') : __('Add New Tax') }}</h2></div>
            <div class="card-body space-y-3">
                <x-form-errors />
                <label class="form-label" for="name">{{ __('Tax Type') }}</label>
                <input id="name" name="name" value="{{ old('name', $editing?->name) }}" required class="form-input" placeholder="VAT">
                <p class="form-hint">{{ __('Set the rate per product on the product form. Active taxes are added to the price customers see.') }}</p>
                <div class="flex gap-2">
                    <button class="btn-primary flex-1">{{ __('Save') }}</button>
                    @if ($editing)<a href="{{ route('admin.taxes.index') }}" class="btn-light">{{ __('Cancel') }}</a>@endif
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
