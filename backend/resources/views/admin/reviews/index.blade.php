<x-layouts.admin :title="__('Product Reviews')">
    <x-page-header :title="__('Product Reviews')" :count="$reviews->total()">
        @can('approve_reviews')
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium">
                {{ __('Auto approve new reviews') }}
                <x-switch :url="route('admin.reviews.auto_approve')" :checked="setting_on('review_auto_approve')" />
            </div>
        @endcan
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.reviews.index', array_filter(['status' => $key])) }}"
               class="rounded-full border px-4 py-1.5 font-medium {{ request('status', '') === $key ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-200 bg-white hover:border-brand-500/50' }}">
                {{ __($label) }}
                @if ($key)<span class="ml-1 opacity-75">{{ $counts[$key] ?? 0 }}</span>@endif
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <select name="type" class="form-input w-auto">
                <option value="">{{ __('Product & store reviews') }}</option>
                <option value="product" @selected(request('type') === 'product')>{{ __('Product reviews') }}</option>
                <option value="store" @selected(request('type') === 'store')>{{ __('Store reviews') }}</option>
            </select>
            <select name="rating" class="form-input w-auto">
                <option value="">{{ __('Any rating') }}</option>
                @foreach ([5, 4, 3, 2, 1] as $n)<option value="{{ $n }}" @selected(request('rating') == $n)>{{ $n }} ★</option>@endforeach
            </select>
            <select name="sort" class="form-input w-auto">
                <option value="">{{ __('Newest first') }}</option>
                <option value="rating_desc" @selected(request('sort') === 'rating_desc')>{{ __('Rating (High > Low)') }}</option>
                <option value="rating_asc" @selected(request('sort') === 'rating_asc')>{{ __('Rating (Low > High)') }}</option>
            </select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="photo" value="1" @checked(request('photo')) class="rounded border-slate-300 text-brand-500"> {{ __('With photo') }}</label>
            <input type="search" name="search" value="{{ request('search') }}" class="form-input min-w-56 flex-1" placeholder="{{ __('Customer, comment or product') }}">
            <button class="btn-light">{{ __('Filter') }}</button>
        </form>

        <div class="divide-y divide-slate-100">
            @forelse ($reviews as $review)
                <div class="grid gap-4 p-5 lg:grid-cols-[220px_1fr_auto]" x-data="{ status: @js($review->status), reply: @js($review->admin_reply), replying: false }">
                    <div class="flex items-start gap-3">
                        @if ($review->product)
                            @if ($thumb = $review->product->thumbnailUrl())<img src="{{ $thumb }}" alt="" class="size-12 rounded-lg object-cover">@endif
                            <p class="line-clamp-3 text-sm font-medium">{{ $review->product->name }}</p>
                        @else
                            <span class="badge bg-navy-700 text-white">{{ __('Store review') }}</span>
                        @endif
                    </div>
                    <div class="text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="tracking-wider text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                            <b>{{ $review->name }}</b>
                            @if ($review->phone)<span class="text-slate-500">{{ $review->phone }}</span>@endif
                            @if ($review->verified)<span class="badge bg-emerald-100 text-emerald-700">✔ {{ __('Verified buyer') }}@if ($review->order) · #{{ $review->order->code }}@endif</span>@endif
                            <span class="text-xs text-slate-400">{{ format_date($review->created_at) }} · 👍 {{ $review->helpful_count }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-slate-700">{{ $review->comment }}</p>
                        @if ($review->photo)
                            <a href="{{ $review->photo->url }}" target="_blank" rel="noopener"><img src="{{ $review->photo->url }}" alt="" class="mt-3 h-24 rounded-lg object-cover"></a>
                        @endif
                        <div class="mt-3" x-show="reply && !replying" x-cloak>
                            <p class="rounded-xl bg-slate-50 p-3 text-slate-600"><b>{{ __('Reply') }}:</b> <span x-text="reply"></span></p>
                        </div>
                        @can('reply_reviews')
                            <div class="mt-3" x-show="replying" x-cloak>
                                <textarea x-model="reply" rows="2" class="form-input" placeholder="{{ __('Write a reply shown under the review') }}"></textarea>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" class="btn-primary btn-sm" @click="window.api(@js(route('admin.reviews.reply', $review)), { method: 'POST', body: { admin_reply: reply } }).then((r) => { toast(r.message); replying = false }).catch((e) => toast(e.message, 'error'))">{{ __('Save') }}</button>
                                    <button type="button" class="btn-light btn-sm" @click="replying = false">{{ __('Cancel') }}</button>
                                </div>
                            </div>
                        @endcan
                    </div>
                    <div class="flex flex-wrap items-start gap-2 lg:flex-col lg:items-end">
                        <span class="badge" :class="{ 'bg-amber-100 text-amber-700': status === 'pending', 'bg-emerald-100 text-emerald-700': status === 'approved', 'bg-red-100 text-red-700': status === 'rejected' }" x-text="status"></span>
                        @can('approve_reviews')
                            <div class="flex gap-1">
                                <button type="button" x-show="status !== 'approved'" class="btn-light btn-sm text-emerald-700" @click="window.api(@js(route('admin.reviews.status', $review)), { method: 'POST', body: { status: 'approved' } }).then((r) => { status = 'approved'; toast(r.message) }).catch((e) => toast(e.message, 'error'))">{{ __('Approve') }}</button>
                                <button type="button" x-show="status !== 'rejected'" class="btn-light btn-sm text-red-600" @click="window.api(@js(route('admin.reviews.status', $review)), { method: 'POST', body: { status: 'rejected' } }).then((r) => { status = 'rejected'; toast(r.message) }).catch((e) => toast(e.message, 'error'))">{{ __('Reject') }}</button>
                            </div>
                        @endcan
                        <div class="flex gap-1">
                            @can('reply_reviews')<button type="button" class="btn-light btn-sm" @click="replying = true">{{ __('Reply') }}</button>@endcan
                            @can('delete_reviews')<button type="button" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50" @click="confirmDelete(@js(route('admin.reviews.destroy', $review)))"><x-icon name="trash" class="size-4" /></button>@endcan
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-5 py-16 text-center text-sm text-slate-500">{{ __('No reviews yet. Reviews written on the website appear here for approval.') }}</p>
            @endforelse
        </div>
        <div class="card-body">{{ $reviews->links() }}</div>
    </div>
</x-layouts.admin>
