<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Color;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ColorController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.colors.index', ['colors' => $this->list($request), 'color' => new Color]);
    }

    public function store(Request $request): RedirectResponse
    {
        Color::query()->create($this->validated($request));

        return back()->with('success', __('Color has been inserted successfully'));
    }

    public function edit(Request $request, Color $color): View
    {
        return view('admin.colors.index', ['colors' => $this->list($request), 'color' => $color]);
    }

    public function update(Request $request, Color $color): RedirectResponse
    {
        $color->update($this->validated($request));

        return redirect()->route('admin.colors.index')->with('success', __('Color has been updated successfully'));
    }

    public function destroy(Color $color): JsonResponse
    {
        $color->delete();

        return response()->json(['success' => true, 'message' => __('Color has been deleted successfully')]);
    }

    /** "Color filter activation": shows a colour filter on the storefront. */
    public function filterActivation(Request $request): JsonResponse
    {
        BusinessSetting::put('color_filter_activation', $request->boolean('value') ? '1' : '0');

        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }

    private function list(Request $request)
    {
        return Color::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')->paginate(15)->withQueryString();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
