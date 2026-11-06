<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' | ' : '' }}{{ get_setting('system_name', config('app.name')) }}</title>
    <link rel="icon" href="{{ uploaded_asset(get_setting('site_icon')) ?? asset('images/jihan-mart-logo.jpg') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.__mediaRoutes = {
            json: @js(route('admin.uploads.json')),
            store: @js(route('admin.uploads.store')),
            byIds: @js(route('admin.uploads.by_ids')),
        };
        window.__flash = @js(collect(['success', 'error', 'warning'])->filter(fn ($k) => session()->has($k))->map(fn ($k) => [$k, session($k)])->values());
    </script>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 text-navy-700 antialiased" x-data>
    {{-- mobile backdrop --}}
    <div x-cloak x-show="$store.sidebar.mobileOpen" x-transition.opacity @click="$store.sidebar.mobileOpen = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 flex flex-col bg-sidebar text-slate-300 transition-all duration-300"
        :class="[$store.sidebar.collapsed ? 'lg:w-20' : 'lg:w-72', $store.sidebar.mobileOpen ? 'w-72 translate-x-0' : 'w-72 -translate-x-full lg:translate-x-0']"
    >
        <a href="{{ route('admin.dashboard') }}" class="flex h-16 shrink-0 items-center gap-2 border-b border-white/5 px-5">
            @if ($logo = uploaded_asset(get_setting('system_logo_white')))
                <img src="{{ $logo }}" alt="{{ get_setting('system_name') }}" class="h-9 w-auto" :class="$store.sidebar.collapsed && 'lg:hidden'">
            @else
                <span class="flex items-center gap-2.5" :class="$store.sidebar.collapsed && 'lg:hidden'">
                    <img src="{{ asset('images/jihan-mart-logo.jpg') }}" alt="" class="size-10 rounded-full bg-white object-cover ring-2 ring-brand-500/60">
                    <span class="text-xl font-extrabold tracking-wide"><span class="text-white">Jihan</span> <span class="text-brand-500">Mart</span></span>
                </span>
            @endif
            <img x-cloak x-show="$store.sidebar.collapsed" src="{{ asset('images/jihan-mart-logo.jpg') }}" alt="Jihan Mart" class="hidden size-10 rounded-full bg-white object-cover ring-2 ring-brand-500/60 lg:block">
        </a>

        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4 [scrollbar-width:thin]">
            @foreach ($adminMenu as $item)
                @include('layouts.partials.admin-menu-item', ['item' => $item, 'depth' => 0])
            @endforeach
        </nav>
    </aside>

    {{-- Main column --}}
    <div class="flex min-h-screen flex-col transition-all duration-300" :class="$store.sidebar.collapsed ? 'lg:pl-20' : 'lg:pl-72'">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-2 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" @click="$store.sidebar.toggle()" class="grid size-10 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" aria-label="{{ __('Toggle sidebar') }}">
                <x-icon name="menu" />
            </button>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" title="{{ __('View website') }}">
                <x-icon name="external" />
            </a>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                @can('create_orders')
                    <a href="{{ route('admin.soon', 'create-order') }}" class="btn-primary hidden sm:inline-flex">
                        <x-icon name="plus" class="size-4" /> {{ __('Create Order') }}
                    </a>
                @endcan
                @can('clear_cache')
                    <form method="POST" action="{{ route('admin.clear_cache') }}">
                        @csrf
                        <button class="btn-light hidden md:inline-flex" title="{{ __('Clear Cache') }}">
                            <x-icon name="refresh" class="size-4" /> {{ __('Clear Cache') }}
                        </button>
                    </form>
                @endcan

                {{-- notifications (order events arrive in phase 3) --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="grid size-10 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" aria-label="{{ __('Notifications') }}">
                        <x-icon name="bell" />
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-xl">
                        <p class="font-semibold">{{ __('Notifications') }}</p>
                        <p class="mt-2 text-slate-500">{{ __('No new notifications') }}</p>
                    </div>
                </div>

                {{-- language --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex h-10 items-center gap-1 rounded-xl px-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                        <x-icon name="language" class="size-5" />
                        <span class="hidden sm:inline">{{ \App\Http\Middleware\SetLocale::LOCALES[app()->getLocale()] ?? 'English' }}</span>
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-40 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl">
                        @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)
                            <form method="POST" action="{{ route('admin.locale') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $code }}">
                                <button class="flex w-full items-center justify-between px-4 py-2 text-left text-sm hover:bg-slate-50">
                                    {{ $label }}
                                    @if (app()->getLocale() === $code) <x-icon name="check" class="size-4 text-brand-500" /> @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>

                {{-- account --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl p-1 pr-2 hover:bg-slate-100">
                        @if ($avatar = uploaded_asset(auth()->user()->avatar_upload_id))
                            <img src="{{ $avatar }}" alt="" class="size-8 rounded-full object-cover">
                        @else
                            <span class="grid size-8 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-accent-500 text-sm font-bold text-white">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        @endif
                        <span class="hidden text-left leading-tight md:block">
                            <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                            <span class="block text-xs capitalize text-slate-500">{{ auth()->user()->isSuperAdmin() ? __('Super admin') : (auth()->user()->getRoleNames()->first() ?? __('Staff')) }}</span>
                        </span>
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-48 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl">
                        <a href="{{ route('admin.profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-icon name="user" class="size-4" /> {{ __('Profile') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"><x-icon name="logout" class="size-4" /> {{ __('Logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            {{ $slot }}
        </main>

        <footer class="px-6 pb-6 text-center text-xs text-slate-400">
            © {{ date('Y') }} {{ get_setting('system_name', 'Jihan Mart') }} v{{ config('app.version', '1.0') }}
        </footer>
    </div>

    {{-- toasts --}}
    <div class="pointer-events-none fixed right-4 top-4 z-[70] flex w-80 flex-col gap-2">
        <template x-for="t in $store.toasts.items" :key="t.id">
            <div x-transition class="pointer-events-auto flex items-start gap-3 rounded-2xl border bg-white p-4 text-sm shadow-lg"
                 :class="{ 'border-emerald-200': t.type === 'success', 'border-red-200': t.type === 'error', 'border-amber-200': t.type === 'warning' }">
                <span class="mt-0.5 size-2.5 shrink-0 rounded-full" :class="{ 'bg-emerald-500': t.type === 'success', 'bg-red-500': t.type === 'error', 'bg-amber-500': t.type === 'warning' }"></span>
                <p class="flex-1" x-text="t.message"></p>
                <button type="button" @click="$store.toasts.remove(t.id)" class="text-slate-400 hover:text-slate-600"><x-icon name="x" class="size-4" /></button>
            </div>
        </template>
    </div>

    {{-- shared delete confirmation --}}
    <div x-cloak x-show="$store.confirm.open" class="fixed inset-0 z-[60] grid place-items-center bg-slate-900/50 p-4" x-transition.opacity @keydown.escape.window="$store.confirm.open = false">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl" @click.outside="$store.confirm.open = false">
            <div class="mx-auto grid size-12 place-items-center rounded-full bg-red-100 text-red-600"><x-icon name="trash" /></div>
            <h3 class="mt-4 text-lg font-semibold" x-text="$store.confirm.title || @js(__('Delete Confirmation'))"></h3>
            <p class="mt-1 text-sm text-slate-500" x-text="$store.confirm.message || @js(__('Are you sure to delete this?'))"></p>
            <div class="mt-6 flex justify-center gap-3">
                <button type="button" class="btn-light" @click="$store.confirm.open = false">{{ __('Cancel') }}</button>
                <button type="button" class="btn-danger" :disabled="$store.confirm.busy" @click="$store.confirm.run()">{{ __('Delete') }}</button>
            </div>
        </div>
    </div>

    @include('layouts.partials.media-picker')
    @stack('scripts')
</body>
</html>
