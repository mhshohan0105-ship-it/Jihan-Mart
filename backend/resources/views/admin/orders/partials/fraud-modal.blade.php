{{-- fraud check modal body, used inside orderModals --}}
<template x-if="modal === 'fraud'">
    <div>
        <div class="card-header">
            <h3 class="card-title">{{ __('Customer Fraud Check') }} · <span x-text="order.phone"></span></h3>
            <div class="flex gap-2">
                <button type="button" class="btn-light btn-sm" :disabled="fraudBusy" @click="openFraud(order, true)">{{ __('Refresh Live') }}</button>
                <button type="button" @click="modal = null"><x-icon name="x" /></button>
            </div>
        </div>
        <div class="card-body space-y-4 text-sm" x-show="fraud">
            <div class="rounded-xl p-4 text-white" :class="{ safe: 'bg-emerald-600', medium: 'bg-amber-500', risky: 'bg-red-600', new: 'bg-slate-500' }[fraud?.overall.level]">
                <p class="text-xs uppercase tracking-wide opacity-80">{{ __('Combined Delivery Trust Score') }}</p>
                <p class="text-xl font-bold" x-text="fraud?.overall.label"></p>
                <p x-show="fraud?.overall.rate !== null"><span x-text="fraud?.overall.rate"></span>% {{ __('success') }} · <span x-text="fraud?.overall.total"></span> {{ __('parcels') }}</p>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="font-semibold">Steadfast</p>
                    <template x-if="fraud?.steadfast && !fraud.steadfast.error">
                        <dl class="mt-2 grid grid-cols-2 gap-1">
                            <dt class="text-slate-500">{{ __('Total') }}</dt><dd x-text="fraud.steadfast.total"></dd>
                            <dt class="text-slate-500">{{ __('Delivered') }}</dt><dd x-text="fraud.steadfast.delivered"></dd>
                            <dt class="text-slate-500">{{ __('Cancelled/Returned') }}</dt><dd x-text="fraud.steadfast.cancelled"></dd>
                            <dt class="text-slate-500">{{ __('Success rate') }}</dt><dd x-text="fraud.steadfast.success_rate === null ? '—' : fraud.steadfast.success_rate + '%'"></dd>
                        </dl>
                    </template>
                    <p class="mt-2 text-red-600" x-show="fraud?.steadfast?.error" x-text="fraud?.steadfast?.error"></p>
                    <p class="mt-2 text-slate-500" x-show="!fraud?.steadfast">{{ __('Not checked yet. Press Refresh Live (needs Steadfast API keys).') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="font-semibold">{{ __('Store history') }} (Jihan Mart)</p>
                    <dl class="mt-2 grid grid-cols-2 gap-1">
                        <dt class="text-slate-500">{{ __('Orders') }}</dt><dd x-text="fraud?.store.total"></dd>
                        <dt class="text-slate-500">{{ __('Delivered') }}</dt><dd x-text="fraud?.store.delivered"></dd>
                        <dt class="text-slate-500">{{ __('Cancelled/Returned') }}</dt><dd x-text="fraud?.store.cancelled"></dd>
                    </dl>
                </div>
            </div>
            <p class="text-xs text-slate-400" x-show="fraud?.checked_at">{{ __('Courier data checked') }}: <span x-text="fraud?.checked_at"></span></p>
        </div>
    </div>
</template>
