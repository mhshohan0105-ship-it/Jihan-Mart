<x-layouts.admin :title="__('Shipping Countries')">
    <x-page-header :title="__('Shipping Countries')" :count="$countries->total()" />
    <div class="card max-w-3xl">
        <form method="GET" class="card-header">
            <h2 class="card-title">{{ __('Countries') }}</h2>
            <input type="search" name="search" value="{{ request('search') }}" class="form-input w-56" placeholder="{{ __('Type name & Enter') }}">
        </form>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('Code') }}</th><th class="px-5 py-3">{{ __('Zones') }}</th><th class="px-5 py-3">{{ __('Show/Hide') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($countries as $country)
                    <tr>
                        <td class="px-5 py-3 text-slate-500">{{ $countries->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-3 font-medium">{{ $country->name }}</td>
                        <td class="px-5 py-3">{{ $country->code }}</td>
                        <td class="px-5 py-3">{{ $country->states_count }}</td>
                        <td class="px-5 py-3"><x-switch :url="route('admin.shipping.countries.toggle', $country)" :checked="$country->status" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="card-body">{{ $countries->links() }}</div>
    </div>
</x-layouts.admin>
