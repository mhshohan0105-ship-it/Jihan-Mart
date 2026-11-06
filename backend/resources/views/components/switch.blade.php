@props(['url', 'field' => null, 'checked' => false, 'disabled' => false])
{{-- Inline on/off switch that saves instantly (POST {field, value}) and shows a toast. --}}
<button type="button" role="switch"
        x-data="switchToggle({ url: @js($url), field: @js($field), value: @js((bool) $checked) })"
        @click="flip()" :aria-checked="on.toString()" @disabled($disabled)
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition disabled:opacity-50"
        :class="on ? 'bg-brand-500' : 'bg-slate-300'" {{ $attributes }}>
    <span class="inline-block size-5 rounded-full bg-white shadow transition" :class="on ? 'translate-x-5.5' : 'translate-x-0.5'"></span>
</button>
