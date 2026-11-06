<x-layouts.admin :title="__('Uploaded Files')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('All uploaded files') }}</h1>
        @can('add_uploads')
            <a href="{{ route('admin.uploads.create') }}" class="btn-primary"><x-icon name="upload" class="size-4" /> {{ __('Upload New File') }}</a>
        @endcan
    </div>

    <div class="card">
        <form method="GET" class="card-header flex-wrap">
            <h2 class="card-title">{{ __('All files') }}</h2>
            <div class="flex flex-wrap gap-2">
                <select name="sort" class="form-input w-auto" onchange="this.form.submit()">
                    @foreach (['newest' => 'Sort by newest', 'oldest' => 'Sort by oldest', 'smallest' => 'Sort by smallest', 'largest' => 'Sort by largest'] as $k => $label)
                        <option value="{{ $k }}" @selected($sort === $k)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <input type="search" name="search" value="{{ $search }}" class="form-input w-56" placeholder="{{ __('Search your files') }}">
                <button class="btn-light">{{ __('Search') }}</button>
            </div>
        </form>

        <div class="card-body">
            @if ($files->isEmpty())
                <p class="py-16 text-center text-sm text-slate-500">{{ __('No files found') }}</p>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5 2xl:grid-cols-6">
                    @foreach ($files as $file)
                        <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white" x-data="{ menu: false, info: false }">
                            <div class="aspect-square bg-slate-50">
                                @if ($file->type === 'image')
                                    <img src="{{ $file->url }}" alt="{{ $file->file_original_name }}" class="size-full object-cover" loading="lazy">
                                @else
                                    <div class="grid size-full place-items-center"><x-icon name="document" class="size-10 text-slate-300" /></div>
                                @endif
                            </div>
                            <div class="px-3 py-2">
                                <p class="truncate text-sm font-medium" title="{{ $file->file_original_name }}">{{ $file->file_original_name }}</p>
                                <p class="text-xs text-slate-500">{{ strtoupper($file->extension) }} · {{ $file->size_label }}</p>
                            </div>

                            <div class="absolute right-2 top-2" @click.outside="menu = false">
                                <button type="button" @click="menu = !menu" class="grid size-8 place-items-center rounded-full bg-white/90 text-slate-600 shadow" aria-label="{{ __('Options') }}">⋮</button>
                                <div x-cloak x-show="menu" x-transition class="absolute right-0 z-10 mt-1 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl">
                                    <button type="button" @click="info = true; menu = false" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50"><x-icon name="document" class="size-4" /> {{ __('Details Info') }}</button>
                                    <a href="{{ route('admin.uploads.download', $file) }}" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50"><x-icon name="download" class="size-4" /> {{ __('Download') }}</a>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($file->url)).then(() => toast(@js(__('Link copied to clipboard')))); menu = false" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50"><x-icon name="link" class="size-4" /> {{ __('Copy link') }}</button>
                                    @can('delete_uploads')
                                        <button type="button" @click="confirmDelete(@js(route('admin.uploads.destroy', $file))); menu = false" class="flex w-full items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-50"><x-icon name="trash" class="size-4" /> {{ __('Delete') }}</button>
                                    @endcan
                                </div>
                            </div>

                            <div x-cloak x-show="info" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/50 p-4" @keydown.escape.window="info = false">
                                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.outside="info = false">
                                    <h3 class="text-lg font-semibold">{{ __('File Info') }}</h3>
                                    @if ($file->type === 'image')
                                        <img src="{{ $file->url }}" alt="" class="mt-4 max-h-60 w-full rounded-xl object-contain bg-slate-50">
                                    @endif
                                    <dl class="mt-4 grid grid-cols-3 gap-2 text-sm">
                                        <dt class="text-slate-500">{{ __('Name') }}</dt><dd class="col-span-2 break-all">{{ $file->file_original_name }}.{{ $file->extension }}</dd>
                                        <dt class="text-slate-500">{{ __('Size') }}</dt><dd class="col-span-2">{{ $file->size_label }}@if ($file->width) · {{ $file->width }}×{{ $file->height }}px @endif</dd>
                                        <dt class="text-slate-500">{{ __('Uploaded by') }}</dt><dd class="col-span-2">{{ $file->user?->name ?? '—' }}</dd>
                                        <dt class="text-slate-500">{{ __('Uploaded at') }}</dt><dd class="col-span-2">{{ format_date($file->created_at) }}</dd>
                                        <dt class="text-slate-500">URL</dt><dd class="col-span-2 break-all text-xs">{{ $file->url }}</dd>
                                    </dl>
                                    <div class="mt-6 text-right"><button type="button" class="btn-light" @click="info = false">{{ __('Close') }}</button></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $files->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.admin>
