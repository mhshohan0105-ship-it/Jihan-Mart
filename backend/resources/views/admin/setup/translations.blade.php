<x-layouts.admin :title="__('Translation')">
    <x-page-header :title="__('Bangla translation')" :count="$rows->total()">
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ $search }}" class="form-input w-64" placeholder="{{ __('Type key or value and Enter') }}">
            <a href="{{ route('admin.languages.index') }}" class="btn-light">{{ __('Back') }}</a>
        </form>
    </x-page-header>

    <form method="POST" action="{{ route('admin.languages.translations.save', $code) }}" class="card max-w-5xl" x-data="{ extra: [] }">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="w-1/2 px-5 py-3">{{ __('Key (English)') }}</th><th class="px-5 py-3">{{ __('Value (Bangla)') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $key => $value)
                        <tr>
                            <td class="px-5 py-2 text-slate-600">{{ $key }}<input type="hidden" name="keys[]" value="{{ $key }}"></td>
                            <td class="px-5 py-2"><input name="values[]" value="{{ $value }}" class="form-input"></td>
                        </tr>
                    @endforeach
                    <template x-for="(row, i) in extra" :key="i">
                        <tr>
                            <td class="px-5 py-2"><input name="keys[]" x-model="row.k" class="form-input" placeholder="{{ __('English text exactly as shown') }}"></td>
                            <td class="px-5 py-2"><input name="values[]" x-model="row.v" class="form-input"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="px-5 pt-4">{{ $rows->links() }}</div>
        <div class="flex justify-between border-t border-slate-100 px-5 py-4">
            <button type="button" class="btn-light" @click="extra.push({ k: '', v: '' })"><x-icon name="plus" class="size-4" /> {{ __('Add New') }}</button>
            <button class="btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
    <p class="form-hint mt-3">{{ __('Clear a value to fall back to English.') }}</p>
</x-layouts.admin>
