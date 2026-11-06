<x-layouts.admin :title="__('Pages')">
    <x-page-header :title="__('Website Pages')" :count="$pages->count()">
        <a href="{{ route('admin.pages.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add New Page') }}</a>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('URL') }}</th><th class="px-5 py-3">{{ __('Footer column') }}</th><th class="px-5 py-3 text-right">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($pages as $row)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3 font-medium">{{ $row->title }}
                            @if ($row->type !== 'custom')<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ __('Default') }}</span>@endif
                        </td>
                        <td class="px-5 py-3"><a href="{{ $row->url() }}" target="_blank" class="text-brand-600 hover:underline">{{ $row->url() }}</a></td>
                        <td class="px-5 py-3">{{ $row->position ? __(\App\Models\Page::POSITIONS[$row->position]) : '—' }}</td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.pages.edit', $row) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>
                                @if ($row->type === 'custom')
                                    <button type="button" @click="confirmDelete(@js(route('admin.pages.destroy', $row)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
