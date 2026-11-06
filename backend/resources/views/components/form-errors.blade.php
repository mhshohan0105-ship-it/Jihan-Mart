@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700']) }}>
        <p class="font-semibold">{{ __('Please fix the following:') }}</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
