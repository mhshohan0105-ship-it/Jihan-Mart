<x-layouts.admin :title="__('File System & Cache Configuration')">
    <x-page-header :title="__('File System & Cache Configuration')" />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.env_key_update') }}" class="card lg:col-span-2">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Cache & Session') }}</h2></div>
            <div class="card-body space-y-4">
                @foreach (['CACHE_STORE' => ['Cache Driver', ['database' => 'Database', 'file' => 'File', 'redis' => 'Redis']], 'SESSION_DRIVER' => ['Session Driver', ['database' => 'Database', 'file' => 'File', 'redis' => 'Redis']]] as $key => [$label, $options])
                    @php($current = \App\Support\EnvWriter::get($key) ?? 'database')
                    <div>
                        <input type="hidden" name="types[]" value="{{ $key }}">
                        <label class="form-label" for="{{ $key }}">{{ __($label) }}</label>
                        <select id="{{ $key }}" name="{{ $key }}" class="form-input">
                            @foreach ($options as $k => $l)<option value="{{ $k }}" @selected($current === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                @endforeach
                <div class="grid gap-4 md:grid-cols-3">
                    <x-env-setting key="REDIS_HOST" :label="__('Redis Host')" placeholder="127.0.0.1" />
                    <x-env-setting key="REDIS_PORT" :label="__('Redis Port')" placeholder="6379" />
                    <x-env-setting key="REDIS_PASSWORD" :label="__('Redis Password')" secret />
                </div>
                <p class="form-hint">{{ __('Pick Redis only when the server has Redis running, otherwise the site stops working. Changing the session driver logs everyone out.') }}</p>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Save') }}</button></div>
        </form>

        <div class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('File Storage') }}</h2></div>
            <div class="card-body space-y-3 text-sm text-slate-600">
                <p>{{ __('Uploads are stored on this server (public disk). Amazon S3 is not used.') }}</p>
                @can('clear_cache')
                    <form method="POST" action="{{ route('admin.clear_cache') }}">@csrf<button class="btn-light w-full">{{ __('Clear Cache') }}</button></form>
                @endcan
            </div>
        </div>
    </div>
</x-layouts.admin>
