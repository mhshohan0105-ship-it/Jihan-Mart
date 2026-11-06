<x-layouts.admin :title="__('Shipping Cities')">
    <x-page-header :title="__('Shipping Cities')" :count="$cities->total()" />
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form method="GET" class="card-header flex-wrap">
                <select name="state_id" class="form-input w-auto" onchange="this.form.submit()">
                    <option value="">{{ __('All zones') }}</option>
                    @foreach ($states as $s)<option value="{{ $s->id }}" @selected(request('state_id') == $s->id)>{{ $s->name }}</option>@endforeach
                </select>
                <input type="search" name="search" value="{{ request('search') }}" class="form-input w-56" placeholder="{{ __('Type name & Enter') }}">
            </form>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">#</th><th class="px-5 py-3">{{ __('Name') }}</th><th class="px-5 py-3">{{ __('State') }}</th><th class="px-5 py-3">{{ __('Cost') }}</th><th class="px-5 py-3">{{ __('Show/Hide') }}</th><th class="px-5 py-3 text-right">{{ __('Action') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cities as $city)
                        <tr>
                            <td class="px-5 py-3 text-slate-500">{{ $cities->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium">{{ $city->name }}</td>
                            <td class="px-5 py-3">{{ $city->state->name }}</td>
                            <td class="px-5 py-3">{{ format_price($city->cost) }}</td>
                            <td class="px-5 py-3"><x-switch :url="route('admin.shipping.cities.toggle', $city)" :checked="$city->status" /></td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.shipping.cities', array_merge(request()->query(), ['edit' => $city->id])) }}" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><x-icon name="pencil" class="size-4" /></a>
                                    <button type="button" @click="confirmDelete(@js(route('admin.shipping.cities.destroy', $city)))" class="grid size-8 place-items-center rounded-lg text-red-500 hover:bg-red-50"><x-icon name="trash" class="size-4" /></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">{{ __('Nothing found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="card-body">{{ $cities->links() }}</div>
        </div>
        <form method="POST" action="{{ $edit ? route('admin.shipping.cities.update', $edit) : route('admin.shipping.cities.store') }}" class="card h-fit">
            @csrf
            @if ($edit) @method('PUT') @endif
            <div class="card-header"><h2 class="card-title">{{ $edit ? __('Edit') : __('Add New City') }}</h2></div>
            <div class="card-body space-y-4">
                <x-form-errors />
                <div>
                    <label class="form-label" for="name">{{ __('Name') }} *</label>
                    <input id="name" name="name" value="{{ old('name', $edit?->name) }}" required class="form-input" placeholder="Mirpur">
                </div>
                <div>
                    <label class="form-label" for="state_id">{{ __('State') }} *</label>
                    <select id="state_id" name="state_id" class="form-input">
                        @foreach ($states as $s)<option value="{{ $s->id }}" @selected(old('state_id', $edit?->state_id ?? request('state_id')) == $s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="cost">{{ __('Cost') }} (৳) *</label>
                    <input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ old('cost', $edit?->cost ?? 60) }}" required class="form-input">
                </div>
                <div class="flex justify-end gap-2">
                    @if ($edit)<a href="{{ route('admin.shipping.cities') }}" class="btn-light">{{ __('Cancel') }}</a>@endif
                    <button class="btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>

        @if ($edit && app(\App\Services\Couriers\PathaoClient::class)->configured())
            {{-- Pathao needs its own city/zone/area ids for every parcel --}}
            <div class="card h-fit xl:col-start-3" x-data="{
                    url: @js(route('admin.couriers.pathao_locations')), city: @js($edit->pathao_city_id), zone: @js($edit->pathao_zone_id), area: @js($edit->pathao_area_id),
                    cities: [], zones: [], areas: [],
                    async list(type, id) { try { return (await window.api(this.url + '?type=' + type + '&id=' + (id || ''))).data } catch (e) { toast(e.message, 'error'); return [] } },
                    async init() { this.cities = await this.list('cities'); if (this.city) this.zones = await this.list('zones', this.city); if (this.zone) this.areas = await this.list('areas', this.zone) },
                    async save() { const r = await window.api(@js(route('admin.couriers.pathao_mapping', $edit)), { method: 'POST', body: { pathao_city_id: this.city || null, pathao_zone_id: this.zone || null, pathao_area_id: this.area || null } }); toast(r.message) },
                }">
                <div class="card-header"><h2 class="card-title">{{ __('Pathao area for') }} {{ $edit->name }}</h2></div>
                <div class="card-body space-y-3">
                    <select x-model="city" @change="zone = ''; area = ''; areas = []; zones = await list('zones', city)" class="form-input"><option value="">{{ __('Pathao city') }}</option><template x-for="c in cities"><option :value="c.id" x-text="c.name" :selected="String(c.id) === String(city)"></option></template></select>
                    <select x-model="zone" @change="area = ''; areas = await list('areas', zone)" class="form-input"><option value="">{{ __('Pathao zone') }}</option><template x-for="z in zones"><option :value="z.id" x-text="z.name" :selected="String(z.id) === String(zone)"></option></template></select>
                    <select x-model="area" class="form-input"><option value="">{{ __('Pathao area (optional)') }}</option><template x-for="a in areas"><option :value="a.id" x-text="a.name" :selected="String(a.id) === String(area)"></option></template></select>
                    <div class="text-right"><button type="button" class="btn-primary" @click="save()">{{ __('Save') }}</button></div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
