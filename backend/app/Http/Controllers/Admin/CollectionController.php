<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        return view('admin.collections.index', [
            'collections' => Collection::query()->withCount('products')->latest('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.collections.form', ['collection' => new Collection(['status' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Collection::query()->create($this->validated($request));

        return redirect()->route('admin.collections.index')->with('success', __('Collection has been inserted successfully'));
    }

    public function edit(Collection $collection): View
    {
        return view('admin.collections.form', compact('collection'));
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $collection->update($this->validated($request));

        return redirect()->route('admin.collections.index')->with('success', __('Collection has been updated successfully'));
    }

    public function destroy(Collection $collection): JsonResponse
    {
        $collection->delete();

        return response()->json(['success' => true, 'message' => __('Collection has been deleted successfully')]);
    }

    /** Ticking a collection checkbox on the All Products page. */
    public function toggleProduct(Request $request, Collection $collection, Product $product): JsonResponse
    {
        $request->boolean('value')
            ? $collection->products()->syncWithoutDetaching([$product->id])
            : $collection->products()->detach($product->id);

        return response()->json(['success' => true, 'message' => __('Collection updated')]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'image_upload_id' => ['nullable', 'integer'],
        ]);
        $data['status'] = $request->boolean('status');

        return $data;
    }
}
