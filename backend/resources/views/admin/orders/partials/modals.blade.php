{{-- used inside x-data="orderModals(...)" --}}
<div x-cloak x-show="modal" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/50 p-4" @keydown.escape.window="modal = null">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl" @click.outside="modal = null">

        {{-- call history --}}
        <template x-if="modal === 'calls'">
            <div>
                <div class="card-header"><h3 class="card-title">{{ __('Call History') }} · #<span x-text="order.code"></span></h3><button type="button" @click="modal = null"><x-icon name="x" /></button></div>
                <div class="card-body space-y-4 text-sm" x-show="calls">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 p-4">
                        <div><p class="font-semibold" x-text="calls?.order.name"></p><p class="text-slate-500" x-text="calls?.order.phone"></p></div>
                        <div class="text-center"><p class="text-2xl font-bold" x-text="calls?.total"></p><p class="text-xs text-slate-500">{{ __('Total calls') }}</p></div>
                        <a :href="'tel:' + calls?.order.phone" @click="logCall(order, null).then(() => openCalls(order))" class="btn-primary">📞 {{ __('Call Now') }}</a>
                    </div>
                    <div x-show="calls && Object.keys(calls.breakdown).length">
                        <p class="mb-1 font-semibold">{{ __('Caller Breakdown') }}</p>
                        <div class="flex flex-wrap gap-2"><template x-for="(n, who) in calls?.breakdown"><span class="badge bg-slate-100 text-slate-700" x-text="who + ': ' + n"></span></template></div>
                    </div>
                    <div class="flex gap-2">
                        <input x-model="callNote" @keydown.enter.prevent="addCallNote()" class="form-input" placeholder="{{ __('Add a note for this call, e.g. Confirmed order, Will call later, Not answered') }}">
                        <button type="button" class="btn-light whitespace-nowrap" :disabled="busy" @click="addCallNote()">{{ __('Add Log / Note') }}</button>
                    </div>
                    <table class="w-full">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-3 py-2">#</th><th class="px-3 py-2">{{ __('Called By') }}</th><th class="px-3 py-2">{{ __('Date & Time') }}</th><th class="px-3 py-2">{{ __('Note / Remarks') }}</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(c, i) in calls?.calls || []"><tr><td class="px-3 py-2" x-text="i + 1"></td><td class="px-3 py-2" x-text="c.by"></td><td class="px-3 py-2" x-text="c.at"></td><td class="px-3 py-2" x-text="c.note || '—'"></td></tr></template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        {{-- comment --}}
        <template x-if="modal === 'comment'">
            <div>
                <div class="card-header"><h3 class="card-title">{{ __('Order Comment') }} · #<span x-text="order.code"></span></h3><button type="button" @click="modal = null"><x-icon name="x" /></button></div>
                <div class="card-body space-y-3">
                    <textarea x-model="comment" rows="4" class="form-input" placeholder="{{ __('Internal note for the team') }}"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-light" @click="modal = null">{{ __('Cancel') }}</button>
                        <button type="button" class="btn-primary" :disabled="busy" @click="saveComment()">{{ __('Save Comment') }}</button>
                    </div>
                </div>
            </div>
        </template>

        @include('admin.orders.partials.fraud-modal')

        {{-- activity log --}}
        <template x-if="modal === 'activity'">
            <div>
                <div class="card-header"><h3 class="card-title">{{ __('Order Activity & Audit Log') }} · #<span x-text="order.code"></span></h3><button type="button" @click="modal = null"><x-icon name="x" /></button></div>
                <div class="card-body">@include('admin.orders.partials.timeline-js')</div>
            </div>
        </template>
    </div>
</div>
