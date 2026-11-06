@php($editing = $staff->exists)
<x-layouts.admin :title="$editing ? __('Edit Staff') : __('Staff Information')">
    <x-page-header :title="$editing ? __('Edit Staff') : __('Staff Information')">
        <a href="{{ route('admin.staffs.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.staffs.update', $staff) : route('admin.staffs.store') }}" class="card max-w-2xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body space-y-4">
            <x-form-errors />
            <div><label class="form-label" for="name">{{ __('Name') }} *</label><input id="name" name="name" value="{{ old('name', $staff->name) }}" required class="form-input"></div>
            <div><label class="form-label" for="email">{{ __('Email') }} *</label><input id="email" type="email" name="email" value="{{ old('email', $staff->email) }}" required class="form-input"></div>
            <div><label class="form-label" for="phone">{{ __('Phone') }}</label><input id="phone" name="phone" value="{{ old('phone', $staff->phone) }}" class="form-input"></div>
            <div>
                <label class="form-label" for="password">{{ __('Password') }} {{ $editing ? '' : '*' }}</label>
                <input id="password" type="password" name="password" autocomplete="new-password" @required(! $editing) class="form-input" placeholder="{{ $editing ? __('Leave empty to keep the current password') : __('At least 8 characters') }}">
            </div>
            <div>
                <label class="form-label" for="role">{{ __('Role') }} *</label>
                <select id="role" name="role" required class="form-input">
                    <option value="">{{ __('Select Role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', $staff->exists ? $staff->roles->first()?->name : null) === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-layouts.admin>
