<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Login') }} | {{ get_setting('system_name', config('app.name')) }}</title>
    <link rel="icon" href="{{ asset('images/jihan-mart-logo.jpg') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-navy-900 antialiased">
<div class="relative grid min-h-screen place-items-center overflow-hidden p-4">
    @if ($background)
        <img src="{{ $background }}" alt="" class="absolute inset-0 size-full object-cover opacity-40">
    @else
        <div class="absolute inset-0 bg-[radial-gradient(120%_90%_at_80%_30%,#2c4a8f_0%,#1b2b57_45%,#111c3a_100%)]"></div>
        <div class="absolute -left-32 -top-32 size-[28rem] rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="absolute -bottom-40 right-0 size-[30rem] rounded-full bg-navy-500/50 blur-3xl"></div>
    @endif

    <div class="relative w-full max-w-md rounded-3xl bg-white/95 p-8 shadow-2xl backdrop-blur">
        <div class="mb-8 text-center">
            @if ($logo = uploaded_asset(get_setting('system_logo_black')))
                <img src="{{ $logo }}" alt="{{ get_setting('system_name') }}" class="mx-auto h-12 w-auto">
            @else
                <img src="{{ asset('images/jihan-mart-logo.jpg') }}" alt="Jihan Mart" class="mx-auto size-36 rounded-full object-cover shadow-lg ring-4 ring-brand-500/20">
            @endif
            <h1 class="mt-4 text-lg font-semibold text-navy-700">{{ __('Login to your account.') }}</h1>
        </div>

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-input">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div x-data="{ show: false }">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password" class="form-input pr-16">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-slate-500" x-text="show ? @js(__('Hide')) : @js(__('Show'))"></button>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                {{ __('Remember Me') }}
            </label>
            <button class="btn-primary w-full py-3">{{ __('Login') }}</button>
        </form>
    </div>
</div>
</body>
</html>
