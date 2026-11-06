<x-layouts.admin :title="__('Languages')">
    <x-page-header :title="__('Languages')" />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.business_settings.update') }}" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Default Language') }}</h2></div>
            <div class="card-body space-y-3">
                <input type="hidden" name="types[]" value="default_language">
                <select name="default_language" class="form-input">
                    @foreach ($languages as $code => $name)<option value="{{ $code }}" @selected(get_setting('default_language', config('app.locale')) === $code)>{{ $name }}</option>@endforeach
                </select>
                <p class="form-hint">{{ __('Admin panel language for staff who have not picked one from the top bar.') }}</p>
                <button class="btn-primary w-full">{{ __('Save') }}</button>
            </div>
        </form>

        <div class="card overflow-x-auto lg:col-span-2">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Code') }}</th><th class="px-5 py-3">RTL</th><th class="px-5 py-3 text-right">{{ __('Options') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($languages as $code => $name)
                        <tr>
                            <td class="px-5 py-3 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 font-medium">{{ $name }}</td>
                            <td class="px-5 py-3 font-mono">{{ $code }}</td>
                            <td class="px-5 py-3">{{ __('No') }}</td>
                            <td class="px-5 py-3 text-right">
                                @if ($code === 'en')
                                    <span class="text-xs text-slate-400">{{ __('Source language') }}</span>
                                @else
                                    <a href="{{ route('admin.languages.translations', $code) }}" class="btn-light btn-sm">{{ __('Translation') }} ({{ $counts[$code] }})</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="form-hint px-5 pb-4">{{ __('The storefront has its own English / Bangla switch; this list covers the admin panel.') }}</p>
        </div>
    </div>
</x-layouts.admin>
