{{-- date preset + custom range; inside a GET form --}}
<div class="flex flex-wrap items-end gap-2" x-data="{ range: @js($range->preset) }">
    <div>
        <label class="form-label">{{ __('Date Range') }}</label>
        <select name="range" x-model="range" class="form-input w-auto" @change="range !== 'custom' && $el.form.submit()">
            @foreach (\App\Support\DateRange::PRESETS as $k => $label)<option value="{{ $k }}">{{ __($label) }}</option>@endforeach
        </select>
    </div>
    <template x-if="range === 'custom'">
        <div class="flex gap-2">
            <input name="date_from" value="{{ $range->preset === 'custom' ? $range->from?->format('Y-m-d') : '' }}" x-data="datePicker()" class="form-input w-36" placeholder="{{ __('From') }}">
            <input name="date_to" value="{{ $range->preset === 'custom' ? $range->to?->format('Y-m-d') : '' }}" x-data="datePicker()" class="form-input w-36" placeholder="{{ __('To') }}">
        </div>
    </template>
</div>
