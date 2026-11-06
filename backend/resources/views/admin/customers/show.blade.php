<x-layouts.admin :title="$customer->name">
    <x-page-header :title="$customer->name">
        <a href="{{ route('admin.customers.index') }}" class="btn-light">{{ __('Back') }}</a>
    </x-page-header>
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card h-fit">
            <div class="card-body space-y-1 text-sm">
                <p class="text-lg font-semibold">{{ $customer->name }}</p>
                <p><a href="tel:{{ $customer->phone }}" class="text-brand-600">{{ $customer->phone }}</a></p>
                @if ($customer->email)<p>{{ $customer->email }}</p>@endif
                <p class="text-slate-500">{{ __('Register At') }}: {{ format_date($customer->created_at) }}</p>
                @if ($customer->banned)<span class="badge bg-red-100 text-red-700">{{ __('Banned') }}</span>@endif
                <div class="flex flex-wrap gap-1 pt-3">
                    @foreach (\App\Models\Order::DELIVERY_STATUSES as $k => $label)
                        @if ($stats[$k] ?? 0)<span class="badge {{ \App\Models\Order::STATUS_COLORS[$k] }}">{{ __($label) }}: {{ $stats[$k] }}</span>@endif
                    @endforeach
                </div>
            </div>
        </section>
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Order history') }}</h2></div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">{{ __('Order Code') }}</th><th class="px-4 py-3">{{ __('Date & Time') }}</th><th class="px-4 py-3">{{ __('Amount') }}</th><th class="px-4 py-3">{{ __('Delivery Status') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $o)
                        <tr>
                            <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $o->encryptedId()) }}" class="font-semibold text-brand-600">#{{ $o->code }}</a></td>
                            <td class="px-4 py-3">{{ format_date($o->created_at) }}</td>
                            <td class="px-4 py-3">{{ format_price($o->grand_total) }}</td>
                            <td class="px-4 py-3"><span class="badge {{ \App\Models\Order::STATUS_COLORS[$o->delivery_status] }}">{{ __(\App\Models\Order::DELIVERY_STATUSES[$o->delivery_status]) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="card-body">{{ $orders->links() }}</div>
        </section>
    </div>
</x-layouts.admin>
