@props([
    'key',
    'label',
    'as' => 'text', // text, textarea, image, images, check, color, rich, code
    'hint' => null,
    'placeholder' => null,
    'default' => null,
])
{{-- One business setting field for forms that post to business-settings/update. --}}
@php($value = get_setting($key, $default))
<div {{ $attributes }}>
    <input type="hidden" name="types[]" value="{{ $key }}">
    @if ($as === 'check')
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="{{ $key }}" value="1" @checked(setting_on($key, (bool) $default)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
            {{ $label }}
        </label>
    @elseif (in_array($as, ['image', 'images']))
        <x-upload-field :name="$key" :value="$value" :label="$label" :hint="$hint" :multiple="$as === 'images'" />
    @else
        <label class="form-label" for="{{ $key }}">{{ $label }} @if ($hint)<span class="font-normal text-slate-400">({{ $hint }})</span>@endif</label>
        @if ($as === 'textarea' || $as === 'code')
            <textarea id="{{ $key }}" name="{{ $key }}" rows="{{ $as === 'code' ? 5 : 3 }}" class="form-input {{ $as === 'code' ? 'font-mono text-xs' : '' }}" placeholder="{{ $placeholder }}">{{ $value }}</textarea>
        @elseif ($as === 'rich')
            <div class="rounded-xl border border-slate-200" x-data="richText()">
                <textarea x-ref="input" name="{{ $key }}" class="hidden">{{ $value }}</textarea>
                <div x-ref="editor" class="min-h-32 rounded-b-xl bg-white"></div>
            </div>
        @elseif ($as === 'color')
            <input id="{{ $key }}" type="color" name="{{ $key }}" value="{{ $value ?: '#000000' }}" class="form-input h-11 w-32 p-1">
        @else
            <input id="{{ $key }}" name="{{ $key }}" value="{{ $value }}" class="form-input" placeholder="{{ $placeholder }}">
        @endif
    @endif
</div>
