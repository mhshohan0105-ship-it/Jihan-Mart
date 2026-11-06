<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(): View
    {
        return view('admin.attributes.index', [
            'attributes' => Attribute::query()->with('values')->orderBy('name')->paginate(15),
            'attribute' => new Attribute,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Attribute::query()->create($request->validate(['name' => ['required', 'string', 'max:120']]));

        return back()->with('success', __('Attribute has been inserted successfully'));
    }

    public function edit(Attribute $attribute): View
    {
        return view('admin.attributes.index', [
            'attributes' => Attribute::query()->with('values')->orderBy('name')->paginate(15),
            'attribute' => $attribute,
        ]);
    }

    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $attribute->update($request->validate(['name' => ['required', 'string', 'max:120']]));

        return redirect()->route('admin.attributes.index')->with('success', __('Attribute has been updated successfully'));
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $attribute->delete();

        return response()->json(['success' => true, 'message' => __('Attribute has been deleted successfully')]);
    }

    /** Attribute values page. */
    public function show(Attribute $attribute): View
    {
        return view('admin.attributes.values', ['attribute' => $attribute->load('values')]);
    }

    /** Values for the product form value picker. */
    public function values(Attribute $attribute): JsonResponse
    {
        return response()->json(['data' => $attribute->values()->pluck('value')]);
    }

    public function storeValue(Request $request, Attribute $attribute): RedirectResponse
    {
        $data = $request->validate(['value' => ['required', 'string', 'max:120']]);
        $attribute->values()->firstOrCreate($data);

        return back()->with('success', __('Attribute value has been inserted successfully'));
    }

    public function updateValue(Request $request, AttributeValue $value): RedirectResponse
    {
        $value->update($request->validate(['value' => ['required', 'string', 'max:120']]));

        return back()->with('success', __('Attribute value has been updated successfully'));
    }

    public function destroyValue(AttributeValue $value): JsonResponse
    {
        $value->delete();

        return response()->json(['success' => true, 'message' => __('Attribute value has been deleted successfully')]);
    }
}
