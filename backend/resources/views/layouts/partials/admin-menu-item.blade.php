@php($hasChildren = ! empty($item['children']))
@if ($hasChildren)
    <div x-data="{ open: @js($item['active'] ?? false) }">
        <button type="button"
                @click="$store.sidebar.collapsed && window.innerWidth >= 1024 ? ($store.sidebar.toggle(), open = true) : open = !open"
                class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-sidebar-hover hover:text-white {{ ($item['active'] ?? false) ? 'text-white' : '' }}"
                @if ($depth === 0) title="{{ __($item['label']) }}" @endif>
            @if ($depth === 0)
                <x-icon :name="$item['icon'] ?? 'cube'" class="size-5 shrink-0 {{ ($item['active'] ?? false) ? 'text-brand-500' : 'text-slate-400 group-hover:text-brand-500' }}" />
            @else
                <span class="size-1.5 shrink-0 rounded-full bg-slate-500"></span>
            @endif
            <span class="flex-1 truncate text-left" :class="$store.sidebar.collapsed && 'lg:hidden'">{{ __($item['label']) }}</span>
            <x-icon name="chevron-down" class="size-4 shrink-0 transition" ::class="[open && 'rotate-180', $store.sidebar.collapsed && 'lg:hidden']" />
        </button>
        <div x-show="open && !($store.sidebar.collapsed && window.innerWidth >= 1024)" x-collapse class="ml-5 space-y-0.5 border-l border-white/5 pl-3">
            @foreach ($item['children'] as $child)
                @include('layouts.partials.admin-menu-item', ['item' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    </div>
@else
    <a href="{{ $item['url'] }}"
       class="group flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition {{ $item['active'] ? 'bg-gradient-to-r from-brand-500/20 to-accent-500/10 font-semibold text-white' : 'hover:bg-sidebar-hover hover:text-white' }}"
       @if ($depth === 0) title="{{ __($item['label']) }}" @endif>
        @if ($depth === 0)
            <x-icon :name="$item['icon'] ?? 'cube'" class="size-5 shrink-0 {{ $item['active'] ? 'text-brand-500' : 'text-slate-400 group-hover:text-brand-500' }}" />
        @endif
        <span class="flex-1 truncate" :class="$store.sidebar.collapsed && {{ $depth === 0 ? 'true' : 'false' }} && 'lg:hidden'">{{ __($item['label']) }}</span>
        @isset($item['soon'])
            <span class="rounded-md bg-white/5 px-1.5 py-0.5 text-[10px] font-medium text-slate-500" :class="$store.sidebar.collapsed && 'lg:hidden'">P{{ $item['soon'] }}</span>
        @endisset
    </a>
@endif
