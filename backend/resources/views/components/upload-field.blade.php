@props([
    'name',
    'label' => null,
    'value' => null,
    'multiple' => false,
    'type' => 'image',
    'hint' => null, // e.g. "300x300"
])
{{-- Media library field: stores upload ids (comma separated when multiple) in a hidden input. --}}
<div x-data="uploadField({ value: @js((string) $value), multiple: @js((bool) $multiple), type: @js($type) })" {{ $attributes }}>
    @if ($label)
        <label class="form-label">{{ $label }} @if ($hint)<span class="font-normal text-slate-400">({{ $hint }})</span>@endif</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value">
    <button type="button" @click="browse()" class="flex w-full overflow-hidden rounded-xl border border-slate-200 text-left text-sm hover:border-brand-500/60">
        <span class="bg-slate-50 px-4 py-2.5 font-semibold text-slate-600">{{ __('Browse') }}</span>
        <span class="flex-1 truncate px-4 py-2.5 text-slate-500" x-text="files.length ? files.length + ' ' + @js(__('file(s) selected')) : @js(__('Choose file'))"></span>
    </button>
    <div class="mt-3 flex flex-wrap gap-3" x-show="files.length">
        <template x-for="file in files" :key="file.id">
            <div class="group relative size-24 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <template x-if="file.type === 'image'"><img :src="file.url" alt="" class="size-full object-cover"></template>
                <template x-if="file.type !== 'image'"><div class="grid size-full place-items-center text-xs font-bold uppercase text-slate-400" x-text="file.extension"></div></template>
                <button type="button" @click="remove(file)" class="absolute right-1 top-1 grid size-6 place-items-center rounded-full bg-white/90 text-red-600 shadow opacity-0 transition group-hover:opacity-100" aria-label="{{ __('Remove') }}"><x-icon name="x" class="size-4" /></button>
            </div>
        </template>
    </div>
</div>
