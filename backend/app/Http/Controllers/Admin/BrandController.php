<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** List on the left, "Add New Brand" form on the right (edit uses the same page). */
class BrandController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.brands.index', [
            'brands' => $this->list($request),
            'brand' => new Brand,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Brand::query()->create($this->validated($request));

        return back()->with('success', __('Brand has been inserted successfully'));
    }

    public function edit(Request $request, Brand $brand): View
    {
        return view('admin.brands.index', ['brands' => $this->list($request), 'brand' => $brand]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $brand->update($this->validated($request));

        return redirect()->route('admin.brands.index')->with('success', __('Brand has been updated successfully'));
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return response()->json(['success' => true, 'message' => __('Brand has been deleted successfully')]);
    }

    private function list(Request $request)
    {
        return Brand::query()->withCount('products')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')->paginate(15)->withQueryString();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'logo_upload_id' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string', 'max:190'],
            'meta_description' => ['nullable', 'string'],
        ]);
    }
}
