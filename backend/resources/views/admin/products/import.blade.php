<x-layouts.admin :title="__('Bulk Import')">
    <x-page-header :title="__('Product Bulk Upload')">
        @can('bulk_export_products')
            <a href="{{ route('admin.products.export') }}" class="btn-light"><x-icon name="download" class="size-4" /> {{ __('Bulk Export') }}</a>
        @endcan
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('How it works') }}</h2></div>
            <div class="card-body space-y-4 text-sm">
                <ol class="list-inside list-decimal space-y-2 text-slate-600">
                    <li>{{ __('Download the CSV template and fill one product per row.') }}</li>
                    <li>{{ __('Use category ids in category_ids (separate several with |). Download the id lists below.') }}</li>
                    <li>{{ __('payment_policy is full_cod, partial or prepaid. discount_type is flat or percent.') }}</li>
                    <li>{{ __('thumbnail_url can point to an image on the internet. Tick "download images" to copy it into the media library.') }}</li>
                    <li>{{ __('Save as CSV (UTF-8) and upload. Rows with errors are skipped and listed.') }}</li>
                </ol>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.products.import.template') }}" class="btn-light"><x-icon name="download" class="size-4" /> {{ __('Download CSV') }}</a>
                    <a href="{{ route('admin.products.import.categories') }}" class="btn-light"><x-icon name="download" class="size-4" /> {{ __('Download Category') }}</a>
                    <a href="{{ route('admin.products.import.brands') }}" class="btn-light"><x-icon name="download" class="size-4" /> {{ __('Download Brand') }}</a>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Columns') }}</p>
                    <p class="font-mono text-xs leading-5 text-slate-600">{{ implode(', ', $columns) }}</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.products.import.store') }}" enctype="multipart/form-data" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Upload CSV') }}</h2></div>
            <div class="card-body space-y-4">
                <x-form-errors />
                @if (session('import_errors'))
                    <div class="max-h-48 overflow-y-auto rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        @foreach (session('import_errors') as $err)<p>{{ $err }}</p>@endforeach
                    </div>
                @endif
                <input type="file" name="file" accept=".csv,text/csv" required class="block w-full text-sm file:mr-4 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:font-semibold file:text-brand-600 hover:file:bg-brand-100">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="download_images" value="1" class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                    {{ __('Download images into the media library (slower)') }}
                </label>
                <div class="text-right"><button class="btn-primary"><x-icon name="upload" class="size-4" /> {{ __('Upload CSV') }}</button></div>
            </div>
        </form>
    </div>
</x-layouts.admin>
