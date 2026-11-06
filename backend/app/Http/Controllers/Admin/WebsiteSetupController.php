<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Website Setup: header, footer, appearance (all saved through business-settings/update) and pages. */
class WebsiteSetupController extends Controller
{
    public function header(): View
    {
        return view('admin.website.header');
    }

    public function footer(): View
    {
        return view('admin.website.footer');
    }

    public function appearance(): View
    {
        return view('admin.website.appearance');
    }

    public function pages(): View
    {
        Page::query()->exists() || $this->seedDefaults();

        return view('admin.website.pages', ['pages' => Page::query()->orderByRaw("case type when 'home' then 0 when 'default' then 1 else 2 end")->orderBy('id')->get()]);
    }

    public function createPage(): View
    {
        return view('admin.website.page-form', ['page' => new Page(['type' => 'custom'])]);
    }

    public function storePage(Request $request): RedirectResponse
    {
        Page::query()->create($this->validated($request) + ['type' => 'custom']);

        return redirect()->route('admin.pages.index')->with('success', __('New page has been created successfully'));
    }

    public function editPage(Page $page): View
    {
        if ($page->type === 'home') {
            return view('admin.website.home-page', ['categories' => Category::query()->whereNull('parent_id')->orderBy('name')->get(['id', 'name'])]);
        }

        return view('admin.website.page-form', compact('page'));
    }

    public function updatePage(Request $request, Page $page): RedirectResponse
    {
        abort_if($page->type === 'home', 404);
        $data = $this->validated($request, $page);
        if ($page->type === 'default') {
            unset($data['slug']); // footer links and policies point at these slugs
        }
        $page->update($data);

        return redirect()->route('admin.pages.index')->with('success', __('Page has been updated successfully'));
    }

    public function destroyPage(Page $page): JsonResponse
    {
        abort_unless($page->type === 'custom', 403, __('Default pages cannot be deleted.'));
        $page->delete();

        return response()->json(['success' => true, 'message' => __('Page has been deleted successfully')]);
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $request->merge(['slug' => Str::slug((string) $request->input('slug')) ?: Str::slug((string) $request->input('title'))]);

        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'string', 'max:190', Rule::unique('pages', 'slug')->ignore($page?->id), Rule::notIn(['home', 'admin', 'api'])],
            'content' => ['required', 'string'],
            'position' => ['nullable', Rule::in(array_keys(Page::POSITIONS))],
            'meta_title' => ['nullable', 'string', 'max:190'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'meta_image_upload_id' => ['nullable', 'integer'],
        ]);
    }

    private function seedDefaults(): void
    {
        foreach (Page::DEFAULTS as $slug => [$title, $type]) {
            Page::query()->firstOrCreate(['slug' => $slug], ['title' => $title, 'type' => $type, 'position' => $type === 'default' ? 'shopping_info' : null]);
        }
    }
}
