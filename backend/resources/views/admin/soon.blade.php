<x-layouts.admin :title="__($item['label'])">
    <div class="card mx-auto mt-10 max-w-lg text-center">
        <div class="card-body py-12">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-500"><x-icon name="clock" class="size-7" /></span>
            <h1 class="mt-4 text-xl font-bold">{{ __($item['label']) }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('This page is built in phase :phase.', ['phase' => $item['soon']]) }}</p>
            <a href="{{ route('admin.dashboard') }}" class="btn-light mt-6">{{ __('Back to Dashboard') }}</a>
        </div>
    </div>
</x-layouts.admin>
