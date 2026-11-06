<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->with('parent')
            ->withCount('products')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('name_bn', 'like', '%'.$request->search.'%')))
            ->orderBy('level')->orderByDesc('order_level')->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['mega_section' => 1]), 'tree' => Category::tree()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::query()->create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', __('Category has been inserted successfully'));
    }

    public function edit(Category $category): View
    {
        // a category cannot become a child of itself or of its own children
        $blocked = $category->descendantIds();
        $tree = Category::tree()->reject(fn ($c) => in_array($c->id, $blocked, true));

        return view('admin.categories.form', compact('category', 'tree'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        abort_if(in_array((int) ($data['parent_id'] ?? 0), $category->descendantIds(), true), 422);
        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', __('Category has been updated successfully'));
    }

    public function destroy(Category $category): JsonResponse
    {
        // children move up to this category's parent instead of losing their place
        Category::query()->where('parent_id', $category->id)->get()->each(fn ($c) => $c->update(['parent_id' => $category->parent_id]));
        $category->delete();

        return response()->json(['success' => true, 'message' => __('Category has been deleted successfully')]);
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'name_bn' => ['nullable', 'string', 'max:190'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'order_level' => ['nullable', 'integer'],
            'banner_upload_id' => ['nullable', 'integer'],
            'image_upload_id' => ['nullable', 'integer'],
            'icon' => ['nullable', 'string', 'max:16'],
            'mega_section' => ['nullable', 'integer', 'between:1,4'],
            'meta_title' => ['nullable', 'string', 'max:190'],
            'meta_description' => ['nullable', 'string'],
        ]);
        $data['order_level'] = (int) ($data['order_level'] ?? 0);
        $data['mega_section'] = (int) ($data['mega_section'] ?? 1);
        foreach (['featured', 'single_grid', 'megamenu'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
