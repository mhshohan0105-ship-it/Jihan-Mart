<x-layouts.admin :title="__('Newsletters')">
    <x-page-header :title="__('Send Newsletter')" />

    @if (in_array($mailer, ['log', 'array']))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ __('Mail is not configured yet (MAIL_MAILER = :m). Emails will only be written to the log until SMTP is set up.', ['m' => $mailer]) }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.newsletter.send') }}" class="card max-w-3xl">
        @csrf
        <div class="card-body space-y-5">
            <x-form-errors />
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="to_subscribers" value="1" @checked(old('to_subscribers', true)) class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                {{ __('All subscribers with email (:n)', ['n' => $subscriberEmails]) }}
            </label>
            @if ($phoneOnly)<p class="form-hint">{{ __(':n subscribers gave only a phone number and will not get emails.', ['n' => $phoneOnly]) }}</p>@endif
            <div>
                <label class="form-label" for="user_ids">{{ __('Customers') }}</label>
                <select id="user_ids" name="user_ids[]" multiple x-data="tomSelect()" placeholder="{{ __('Select customers') }}">
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="subject">{{ __('Newsletter subject') }} *</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">{{ __('Newsletter content') }} *</label>
                <div class="rounded-xl border border-slate-200" x-data="richText()">
                    <textarea x-ref="input" name="content" class="hidden">{{ old('content') }}</textarea>
                    <div x-ref="editor" class="min-h-48 rounded-b-xl bg-white"></div>
                </div>
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4"><button class="btn-primary">{{ __('Send') }}</button></div>
    </form>
</x-layouts.admin>
