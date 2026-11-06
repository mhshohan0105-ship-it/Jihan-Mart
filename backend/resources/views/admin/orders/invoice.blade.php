<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice') }} {{ $orders->pluck('code')->implode(', ') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 12mm; }
        @media print { .no-print { display: none !important; } .invoice { break-after: page; box-shadow: none !important; margin: 0 !important; } }
    </style>
</head>
<body class="bg-slate-100 text-navy-700">
    <div class="no-print sticky top-0 z-10 flex justify-center gap-2 bg-white/90 p-3 shadow">
        <button onclick="window.print()" class="btn-primary">{{ __('Print') }}</button>
        <button onclick="window.close()" class="btn-light">{{ __('Close') }}</button>
    </div>
    @foreach ($orders as $order)
        <div class="invoice mx-auto my-6 max-w-3xl bg-white p-8 text-sm shadow">
            <div class="flex items-start justify-between gap-6 border-b-2 border-brand-500 pb-5">
                <div class="flex items-center gap-3">
                    <img src="{{ uploaded_asset(get_setting('system_logo_black')) ?? asset('images/jihan-mart-logo.jpg') }}" alt="" class="size-16 rounded-full object-cover">
                    <div>
                        <p class="text-xl font-extrabold">{{ get_setting('invoice_site_name', get_setting('system_name', 'Jihan Mart')) }}</p>
                        <p class="text-xs text-slate-500">{{ get_setting('invoice_address', get_setting('contact_address')) }}</p>
                        <p class="text-xs text-slate-500">{{ get_setting('invoice_phone', get_setting('contact_phone')) }} {{ get_setting('invoice_email', get_setting('contact_email')) }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold uppercase tracking-wide text-brand-600">{{ __('Invoice') }}</p>
                    <p>{{ __('Invoice No') }}: <b>#{{ $order->code }}</b></p>
                    <p>{{ format_date($order->created_at) }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Bill to') }}</p>
                    <p class="font-semibold">{{ $order->name }}</p>
                    <p>{{ $order->phone }}</p>
                    <p>{{ $order->address }}</p>
                    <p class="text-slate-500">{{ $order->areaLabel() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Payment') }}</p>
                    <p>{{ __(\App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method) }}</p>
                    <p>{{ __(\App\Models\Order::PAYMENT_STATUSES[$order->payment_status]) }}</p>
                </div>
            </div>
            <table class="w-full">
                <thead class="bg-navy-700 text-left text-xs uppercase text-white">
                    <tr>@if ($showImage)<th class="px-3 py-2">{{ __('Image') }}</th>@endif<th class="px-3 py-2">{{ __('Product Name') }}</th><th class="px-3 py-2 text-center">{{ __('Quantity') }}</th><th class="px-3 py-2 text-right">{{ __('Unit Price') }}</th><th class="px-3 py-2 text-right">{{ __('Subtotal') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->details as $d)
                        <tr>
                            @if ($showImage)<td class="px-3 py-2">@if ($img = $d->product?->thumbnailUrl())<img src="{{ $img }}" alt="" class="size-10 rounded object-cover">@endif</td>@endif
                            <td class="px-3 py-2">{{ $d->product_name }} @if ($d->variant)<span class="text-slate-500">({{ $d->variant }})</span>@endif @if ($showSku && $d->sku)<br><small class="text-slate-400">SKU: {{ $d->sku }}</small>@endif</td>
                            <td class="px-3 py-2 text-center">{{ $d->quantity }}</td>
                            <td class="px-3 py-2 text-right">{{ format_price($d->price) }}</td>
                            <td class="px-3 py-2 text-right">{{ format_price($d->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <dl class="ml-auto mt-4 max-w-xs space-y-1">
                <div class="flex justify-between"><dt>{{ __('Total Quantity') }}</dt><dd>{{ $order->details->sum('quantity') }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('Sub Total') }}</dt><dd>{{ format_price($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('Shipping Cost') }}</dt><dd>{{ format_price($order->shipping_cost) }}</dd></div>
                @if ((float) $order->discount + (float) $order->coupon_discount > 0)<div class="flex justify-between"><dt>{{ __('Discount') }}</dt><dd>-{{ format_price((float) $order->discount + (float) $order->coupon_discount) }}</dd></div>@endif
                <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold"><dt>{{ __('Grand Total') }}</dt><dd>{{ format_price($order->grand_total) }}</dd></div>
                @if ((float) $order->advance_amount > 0)<div class="flex justify-between"><dt>{{ __('Advance') }}</dt><dd>-{{ format_price($order->advance_amount) }}</dd></div>@endif
                <div class="flex justify-between rounded bg-brand-50 px-2 py-1 text-base font-bold text-brand-700"><dt>{{ __('TOTAL DUE') }}</dt><dd>{{ format_price($order->due) }}</dd></div>
            </dl>
            <p class="mt-8 border-t border-slate-100 pt-4 text-center text-xs text-slate-500">{{ get_setting('invoice_footer', 'Thanks for shopping from Jihan Mart') }}</p>
        </div>
    @endforeach
</body>
</html>
