<x-layouts.admin :title="__('Upload New File')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('Upload New File') }}</h1>
        <a href="{{ route('admin.uploads.index') }}" class="btn-light">{{ __('Back to uploaded files') }}</a>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">{{ __('Drag & drop your files') }}</h2></div>
        <div class="card-body" x-data="uploader()" @media-uploaded.window="">
            @include('admin.uploads.partials.dropzone')
            <p class="form-hint mt-4">{{ __('Allowed') }}: {{ implode(', ', $accept) }}</p>
        </div>
    </div>
</x-layouts.admin>
