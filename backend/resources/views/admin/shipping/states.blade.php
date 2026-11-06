<x-layouts.admin :title="__('Shipping States')">
    <x-page-header :title="__('Shipping States')" :count="$states->total()" />
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Country') }}</th><th class="px-5 py-3">{{ __('Areas') }}</th><th class="px-5 py-3">{{ __('Show/Hide') }}</th><th class="px-5 py-3 text-right">{{ __('Action') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($states as $state)
                        <tr>
                            <td class="px-5 py-3 text-slate-500">{{ $states->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium">{{ $state->name }}</td>
                            <td class="px-5 py-3">{{ $state->country->name }}</td>
                            <td class="px-5 py-3"><a href="{{ route('admin.shipping.cities', ['state_id' => $state->id]) }}" class="text-brand-600 hover:underline">{{ $state->cities_count }}</a></td>
                            <td class="px-5 py-3"><x-switch :url="route('admin.shipping.states.toggle', $state)" :checked="$state->status" /></td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.shipping.states', ['edit' => $state->id]) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>
                                    <button type="button" @click="confirmDelete(@js(route('admin.shipping.states.destroy', $state)), { message: @js(__('All areas inside it are deleted too.')) })" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="card-body">{{ $states->links() }}</div>
        </div>
        <form method="POST" action="{{ $edit ? route('admin.shipping.states.update', $edit) : route('admin.shipping.states.store') }}" class="card h-fit">
            @csrf
            @if ($edit) @method('PUT') @endif
            <div class="card-header"><h2 class="card-title">{{ $edit ? __('Edit') : __('Add New State') }}</h2></div>
            <div class="card-body space-y-4">
                <x-form-errors />
                <div>
                    <label class="form-label" for="name">{{ __('Name') }} *</label>
                    <input id="name" name="name" value="{{ old('name', $edit?->name) }}" required class="form-input" placeholder="ঢাকার ভিতরে">
                </div>
                <div>
                    <label class="form-label" for="country_id">{{ __('Country') }} *</label>
                    <select id="country_id" name="country_id" class="form-input">
                        @foreach ($countries as $c)<option value="{{ $c->id }}" @selected(old('country_id', $edit?->country_id) == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    @if ($edit)<a href="{{ route('admin.shipping.states') }}" class="btn-light">{{ __('Cancel') }}</a>@endif
                    <button class="btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
