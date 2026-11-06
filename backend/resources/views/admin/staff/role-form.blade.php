@php
    $editing = $role->exists;
    $granted = old('permissions', $granted);
@endphp
<x-layouts.admin :title="$editing ? __('Edit Role') : __('Role Information')">
    <x-page-header :title="$editing ? __('Edit Role').': '.$role->name : __('Role Information')">
        <a href="{{ route('admin.roles.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="max-w-6xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif
        <section class="card">
            <div class="card-body">
                <x-form-errors />
                <label class="form-label" for="name">{{ __('Name') }} *</label>
                <input id="name" name="name" value="{{ old('name', $role->name) }}" required class="form-input max-w-md" placeholder="Order Staff">
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($sections as $section => $names)
                <section class="card" x-data>
                    <div class="card-header">
                        <h2 class="card-title">{{ __($section) }}</h2>
                        <button type="button" class="text-xs font-semibold text-brand-600 hover:underline"
                                @click="const boxes = [...$el.closest('section').querySelectorAll('input[type=checkbox]')]; const on = boxes.some((b) => !b.checked); boxes.forEach((b) => b.checked = on)">{{ __('Select all') }}</button>
                    </div>
                    <div class="card-body space-y-2">
                        @foreach ($names as $name)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $name }}" @checked(in_array($name, $granted, true)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                                {{ __(ucfirst(str_replace('_', ' ', $name))) }}
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="sticky bottom-4 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
