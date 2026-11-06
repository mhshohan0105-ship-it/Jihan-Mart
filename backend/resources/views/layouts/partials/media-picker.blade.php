{{-- The one global file picker. Opened by <x-upload-field> through the "open-media-picker" event. --}}
<div x-data="mediaPicker" x-cloak x-show="open" class="fixed inset-0 z-[55] grid place-items-center bg-slate-900/50 p-2 sm:p-6" x-transition.opacity @keydown.escape.window="open = false">
    <div class="flex h-full max-h-[760px] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="open = false">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
            <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold" :class="tab === 'library' ? 'bg-brand-50 text-brand-600' : 'text-slate-500 hover:bg-slate-50'" @click="tab = 'library'">{{ __('Select File') }}</button>
            <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold" :class="tab === 'upload' ? 'bg-brand-50 text-brand-600' : 'text-slate-500 hover:bg-slate-50'" @click="tab = 'upload'">{{ __('Upload New') }}</button>
            <button type="button" class="ml-auto grid size-9 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" @click="open = false"><x-icon name="x" /></button>
        </div>

        {{-- library --}}
        <div x-show="tab === 'library'" class="flex min-h-0 flex-1 flex-col">
            <div class="flex flex-wrap gap-2 px-5 py-3">
                <select x-model="sort" @change="page = 1; load()" class="form-input w-auto">
                    <option value="newest">{{ __('Sort by newest') }}</option>
                    <option value="oldest">{{ __('Sort by oldest') }}</option>
                    <option value="smallest">{{ __('Sort by smallest') }}</option>
                    <option value="largest">{{ __('Sort by largest') }}</option>
                </select>
                <input type="search" x-model.debounce.400ms="search" @input.debounce.400ms="page = 1; load()" class="form-input min-w-0 flex-1" placeholder="{{ __('Search your files') }}">
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-4">
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                    <template x-for="file in files" :key="file.id">
                        <button type="button" @click="toggle(file)" class="group relative overflow-hidden rounded-xl border-2 bg-slate-50 text-left transition"
                                :class="isSelected(file) ? 'border-brand-500 ring-4 ring-brand-500/15' : 'border-transparent hover:border-slate-300'">
                            <div class="aspect-square">
                                <template x-if="file.type === 'image'"><img :src="file.url" alt="" class="size-full object-cover" loading="lazy"></template>
                                <template x-if="file.type !== 'image'"><div class="grid size-full place-items-center text-xs font-bold uppercase text-slate-400" x-text="file.extension"></div></template>
                            </div>
                            <div class="truncate px-2 py-1 text-[11px] text-slate-600" x-text="file.file_original_name + '.' + file.extension"></div>
                            <span x-show="isSelected(file)" class="absolute right-1.5 top-1.5 grid size-6 place-items-center rounded-full bg-brand-500 text-white"><x-icon name="check" class="size-4" /></span>
                        </button>
                    </template>
                </div>
                <p x-show="!loading && files.length === 0" class="py-16 text-center text-sm text-slate-500">{{ __('No files found') }}</p>
                <div class="py-4 text-center" x-show="page < lastPage">
                    <button type="button" class="btn-light" @click="more()" :disabled="loading">{{ __('Load more') }}</button>
                </div>
            </div>
        </div>

        {{-- upload --}}
        <div x-show="tab === 'upload'" class="min-h-0 flex-1 overflow-y-auto p-5">
            <div x-data="uploader({ onUploaded: (f) => { uploaded(f); } })">
                @include('admin.uploads.partials.dropzone')
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3">
            <p class="text-sm text-slate-500"><span x-text="selected.length"></span> {{ __('file(s) selected') }}</p>
            <div class="flex gap-2">
                <button type="button" class="btn-light" @click="selected = []">{{ __('Clear') }}</button>
                <button type="button" class="btn-primary" @click="confirm()">{{ __('Add Files') }}</button>
            </div>
        </div>
    </div>
</div>
