<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Shipping configuration plus the location lists: countries, states (zones/districts), cities (areas with cost). */
class ShippingController extends Controller
{
    public function configuration(): View
    {
        return view('admin.shipping.configuration');
    }

    public function saveConfiguration(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shipping_type' => ['required', 'in:area_wise,flat_rate'],
            'flat_rate_shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_min_amount' => ['nullable', 'numeric', 'min:0'],
            'default_courier' => ['nullable', 'in:steadfast,pathao'],
        ]);
        foreach ($data as $type => $value) {
            BusinessSetting::put($type, $value ?? '0');
        }
        BusinessSetting::put('free_shipping_enabled', $request->boolean('free_shipping_enabled') ? '1' : '0');

        return back()->with('success', __('Shipping configuration updated'));
    }

    public function countries(Request $request): View
    {
        $countries = Country::query()->withCount('states')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.shipping.countries', compact('countries'));
    }

    public function toggleCountry(Request $request, Country $country): JsonResponse
    {
        $country->update(['status' => $request->boolean('value')]);

        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }

    public function states(Request $request): View
    {
        return view('admin.shipping.states', [
            'states' => State::query()->with('country')->withCount('cities')->orderBy('id')->paginate(15),
            'countries' => Country::query()->where('status', true)->orderBy('name')->get(),
            'edit' => $request->integer('edit') ? State::query()->find($request->integer('edit')) : null,
        ]);
    }

    public function saveState(Request $request, ?State $state = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'country_id' => ['required', Rule::exists('countries', 'id')],
        ]);
        $state ? $state->update($data) : State::query()->create($data + ['status' => true]);

        return redirect()->route('admin.shipping.states')->with('success', __('Saved successfully'));
    }

    public function toggleState(Request $request, State $state): JsonResponse
    {
        $state->update(['status' => $request->boolean('value')]);

        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }

    public function destroyState(State $state): JsonResponse
    {
        $state->delete();

        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }

    public function cities(Request $request): View
    {
        $cities = City::query()->with('state')
            ->when($request->integer('state_id'), fn ($q, $id) => $q->where('state_id', $id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('state_id')->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.shipping.cities', [
            'cities' => $cities,
            'states' => State::query()->orderBy('id')->get(),
            'edit' => $request->integer('edit') ? City::query()->find($request->integer('edit')) : null,
        ]);
    }

    public function saveCity(Request $request, ?City $city = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'state_id' => ['required', Rule::exists('states', 'id')],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);
        $city ? $city->update($data) : City::query()->create($data + ['status' => true]);

        return redirect()->route('admin.shipping.cities', ['state_id' => $data['state_id']])->with('success', __('Saved successfully'));
    }

    public function toggleCity(Request $request, City $city): JsonResponse
    {
        $city->update(['status' => $request->boolean('value')]);

        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }

    public function destroyCity(City $city): JsonResponse
    {
        $city->delete();

        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }

    /** Areas of a district for dependent selects (POS, checkout, order filter). */
    public function getCities(Request $request): JsonResponse
    {
        $cities = City::query()->where('state_id', $request->integer('state_id'))->where('status', true)
            ->orderBy('name')->get(['id', 'name', 'cost']);

        return response()->json(['data' => $cities]);
    }
}
