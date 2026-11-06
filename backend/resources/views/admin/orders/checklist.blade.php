<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Packaging Checklist') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="bg-white p-6 text-sm text-navy-700">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">{{ __('Packaging Checklist') }}</h1>
            <p class="text-slate-500">{{ __(':count confirmed orders', ['count' => $orderCount]) }} · {{ format_date(now()) }}</p>
            @if ($skipped)<p class="text-amber-700">{{ __(':count selected orders were skipped because they are not confirmed.', ['count' => $skipped]) }}</p>@endif
        </div>
        <div class="no-print flex gap-2"><button onclick="window.print()" class="btn-primary">{{ __('Print Checklist') }}</button><button onclick="window.close()" class="btn-light">{{ __('Close') }}</button></div>
    </div>
    <table class="w-full border border-slate-200">
        <thead class="bg-slate-100 text-left text-xs uppercase">
            <tr><th class="px-3 py-2">✓</th><th class="px-3 py-2">{{ __('SKU / Barcode') }}</th><th class="px-3 py-2">{{ __('Image') }}</th><th class="px-3 py-2">{{ __('Product Details') }}</th><th class="px-3 py-2">{{ __('Order Numbers') }}</th><th class="px-3 py-2 text-center">{{ __('Quantity') }}</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($rows as $row)
                <tr>
                    <td class="px-3 py-2"><span class="inline-block size-5 rounded border border-slate-400"></span></td>
                    <td class="px-3 py-2">{{ $row['detail']->sku ?: $row['detail']->product?->barcode }}</td>
                    <td class="px-3 py-2">@if ($img = $row['detail']->product?->thumbnailUrl())<img src="{{ $img }}" alt="" class="size-12 rounded object-cover">@endif</td>
                    <td class="px-3 py-2 font-medium">{{ $row['detail']->product_name }} @if ($row['detail']->variant)<span class="text-slate-500">({{ $row['detail']->variant }})</span>@endif</td>
                    <td class="px-3 py-2 text-xs">{{ $row['codes']->map(fn ($c) => '#'.$c)->implode(', ') }}</td>
                    <td class="px-3 py-2 text-center text-lg font-bold">{{ $row['qty'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">{{ __('No confirmed orders to pack.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
