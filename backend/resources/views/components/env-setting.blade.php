@props(['key', 'label', 'secret' => false, 'placeholder' => null])
{{-- One .env value for forms that post to env-key-update. Secrets show masked; empty keeps the saved value. --}}
@php($current = \App\Support\EnvWriter::get($key))
<div {{ $attributes }}>
    <input type="hidden" name="types[]" value="{{ $key }}">
    <label class="form-label" for="{{ $key }}">{{ $label }}</label>
    @if ($secret)
        <input id="{{ $key }}" name="{{ $key }}" type="password" autocomplete="off" class="form-input" placeholder="{{ \App\Support\EnvWriter::mask($current) ?: __('Not set') }}">
    @else
        <input id="{{ $key }}" name="{{ $key }}" value="{{ $current }}" autocomplete="off" class="form-input" placeholder="{{ $placeholder }}">
    @endif
</div>
