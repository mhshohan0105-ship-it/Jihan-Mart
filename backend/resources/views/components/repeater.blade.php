@props([
    'name',
    'label' => null,
    'rows' => [],
    // key => [type (image|text|textarea), placeholder]
    'fields' => ['image' => ['image', ''], 'link' => ['text', 'https://']],
    'max' => 0,
    'hint' => null,
])
@php
    $rows = collect(is_string($rows) ? (json_decode($rows, true) ?: []) : ($rows ?? []))->values()->map(function ($row) use ($fields) {
        foreach ($fields as $key => [$type]) {
            $row[$key] ??= '';
            if ($type === 'image') {
                $row[$key.'_url'] = uploaded_asset($row[$key]) ?? '';
            }
        }

        return $row;
    })->all();
    $blank = collect($fields)->mapWithKeys(fn ($f, $key) => [$key => ''])->all();
@endphp
{{-- Repeating rows posted as name[i][key]; see repeater() in resources/js/widgets.js --}}
<div x-data="repeater({ rows: @js($rows), blank: @js($blank), max: {{ (int) $max }} })" {{ $attributes->class('space-y-3') }}>
    @if ($label)
        <div class="flex items-center justify-between">
            <label class="form-label mb-0">{{ $label }} @if ($hint)<span class="font-normal text-slate-400">({{ $hint }})</span>@endif @if ($max)<span class="font-normal text-slate-400">· {{ __('max :n', ['n' => $max]) }}</span>@endif</label>
        </div>
    @endif
    <template x-if="!rows.length"><input type="hidden" name="{{ $name }}" value=""></template>
    <template x-for="(row, i) in rows" :key="i">
        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-3">
            @foreach ($fields as $key => [$type, $placeholder])
                @if ($type === 'image')
                    <button type="button" @click="pick(i, '{{ $key }}')" class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-white text-xs text-slate-400 hover:border-brand-500">
                        <template x-if="row.{{ $key }}_url"><img :src="row.{{ $key }}_url" alt="" class="size-full object-cover"></template>
                        <template x-if="!row.{{ $key }}_url"><span>{{ __('Image') }}</span></template>
                    </button>
                    <input type="hidden" :name="`{{ $name }}[${i}][{{ $key }}]`" :value="row.{{ $key }}">
                @elseif ($type === 'textarea')
                    <textarea :name="`{{ $name }}[${i}][{{ $key }}]`" x-model="row.{{ $key }}" rows="2" class="form-input flex-1" placeholder="{{ $placeholder }}"></textarea>
                @else
                    <input :name="`{{ $name }}[${i}][{{ $key }}]`" x-model="row.{{ $key }}" class="form-input flex-1" placeholder="{{ $placeholder }}">
                @endif
            @endforeach
            <button type="button" @click="remove(i)" class="grid size-9 shrink-0 place-items-center rounded-lg text-red-500 hover:bg-red-50" aria-label="{{ __('Remove') }}"><x-icon name="trash" class="size-4" /></button>
        </div>
    </template>
    <button type="button" @click="add()" x-show="!max || rows.length < max" class="btn-light"><x-icon name="plus" class="size-4" /> {{ __('Add New') }}</button>
</div>
