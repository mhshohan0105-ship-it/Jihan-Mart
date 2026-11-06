<x-layouts.admin :title="__('Categories')">
    <x-page-header :title="__('All Categories')" :count="$categories->total()">
        @can('add_categories')
            <a href="{{ route('admin.categories.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add New category') }}</a>
        @endcan
    </x-page-header>

    <div class="card">
        <form method="GET" class="card-header">
            <h2 class="card-title">{{ __('Categories') }}</h2>
            <input type="search" name="search" value="{{ request('search') }}" class="form-input w-64" placeholder="{{ __('Type name & Enter') }}">
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">#</th>
                        <th class="px-5 py-3">{{ __('Name') }}</th>
                        <th class="px-5 py-3">{{ __('Parent Category') }}</th>
                        <th class="px-5 py-3">{{ __('Order Level') }}</th>
                        <th class="px-5 py-3">{{ __('Level') }}</th>
                        <th class="px-5 py-3">{{ __('Products') }}</th>
                        <th class="px-5 py-3">{{ __('Featured') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Options') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 text-slate-500">{{ $categories->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($img = uploaded_asset($category->image_upload_id))
                                        <img src="{{ $img }}" alt="" class="size-9 rounded-lg object-cover">
                                    @else
                                        <span class="grid size-9 place-items-center rounded-lg bg-brand-50 text-lg">{{ $category->icon ?: '🛍️' }}</span>
                                    @endif
                                    <div>
                                        <p class="font-medium">{{ $category->name }}</p>
                                        @if ($category->name_bn)<p class="text-xs text-slate-500">{{ $category->name_bn }}</p>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">{{ $category->parent?->name ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $category->order_level }}</td>
                            <td class="px-5 py-3">{{ $category->level }}</td>
                            <td class="px-5 py-3">{{ $category->products_count }}</td>
                            <td class="px-5 py-3">
                                <x-switch :url="route('admin.toggle', ['categories', $category->id])" field="featured" :checked="$category->featured" :disabled="! auth()->user()->can('edit_categories')" />
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    @can('edit_categories')
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                    @endcan
                                    @can('delete_categories')
                                        <button type="button" @click="confirmDelete(@js(route('admin.categories.destroy', $category)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50" title="{{ __('Delete') }}"><x-icon name="trash" class="size-4" /></button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $categories->links() }}</div>
    </div>
</x-layouts.admin>
