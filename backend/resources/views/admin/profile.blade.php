<x-layouts.admin :title="__('Profile')">
    <h1 class="mb-6 text-2xl font-bold">{{ __('Profile') }}</h1>

    <form method="POST" action="{{ route('admin.profile.update') }}" class="card max-w-2xl">
        @csrf
        @method('PUT')
        <div class="card-body space-y-5">
            @if ($errors->any())
                <div class="rounded-xl bg-red-50 p-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
                </div>
            @endif
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="name">{{ __('Name') }} *</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label" for="phone">{{ __('Phone') }}</label>
                    <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="01XXXXXXXXX" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label" for="email">{{ __('Email') }} *</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="form-input">
            </div>
            <x-upload-field name="avatar_upload_id" :value="$user->avatar_upload_id" :label="__('Avatar')" hint="90x90" />

            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                <p class="mb-4 text-sm font-semibold text-slate-700">{{ __('Change password') }}</p>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="form-label" for="current_password">{{ __('Current password') }}</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="password">{{ __('New password') }}</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="password_confirmation">{{ __('Confirm password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-input">
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4">
            <button class="btn-primary">{{ __('Update Profile') }}</button>
        </div>
    </form>
</x-layouts.admin>
