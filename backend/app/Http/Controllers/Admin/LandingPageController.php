<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Landing Page → Home Pages: single product / campaign sites on their own domains. */
class LandingPageController extends Controller
{
    public function index(): View
    {
        $pages = LandingPage::query()->latest('id')->paginate(15);
        $orders = Order::query()->where('source', 'landing')->whereIn('landing_domain', $pages->pluck('domain'))
            ->selectRaw('landing_domain, count(*) as c')->groupBy('landing_domain')->pluck('c', 'landing_domain');

        return view('admin.landing.index', compact('pages', 'orders'));
    }

    public function create(): View
    {
        return view('admin.landing.form', $this->formData(new LandingPage(['status' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        LandingPage::query()->create($this->validated($request));

        return redirect()->route('admin.landing_pages.index')->with('success', __('Landing page has been created successfully'));
    }

    public function edit(LandingPage $landingPage): View
    {
        return view('admin.landing.form', $this->formData($landingPage));
    }

    public function update(Request $request, LandingPage $landingPage): RedirectResponse
    {
        $landingPage->update($this->validated($request, $landingPage));

        return redirect()->route('admin.landing_pages.index')->with('success', __('Landing page has been updated successfully'));
    }

    public function destroy(LandingPage $landingPage): JsonResponse
    {
        $landingPage->delete();

        return response()->json(['success' => true, 'message' => __('Landing page has been deleted successfully')]);
    }

    private function validated(Request $request, ?LandingPage $page = null): array
    {
        $request->merge(['domain' => LandingPage::normalizeDomain((string) $request->input('domain'))]);
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9.-]+(:\d+)?$/', Rule::unique('landing_pages', 'domain')->ignore($page?->id)],
            'logo_upload_id' => ['nullable', 'integer'],
            'sliders' => ['nullable', 'array'],
            'video_link' => ['nullable', 'url', 'max:255'],
            'features' => ['nullable', 'array'],
            'banners' => ['nullable', 'array'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'contents' => ['nullable', 'array', 'max:3'],
            'contents.*.title' => ['nullable', 'string', 'max:190'],
            'contents.*.body' => ['nullable', 'string'],
            'about' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = fn ($rows) => array_values(array_filter((array) $rows, fn ($r) => is_array($r) && array_filter($r, fn ($v) => $v !== null && $v !== '')));
        $data['sliders'] = $rows($data['sliders'] ?? []);
        $data['features'] = $rows($data['features'] ?? []);
        $data['banners'] = collect(LandingPage::BANNERS)->mapWithKeys(fn ($max, $key) => [$key => array_slice($rows($data['banners'][$key] ?? []), 0, $max)])->all();
        $data['product_ids'] = array_map('intval', $data['product_ids'] ?? []);
        $data['contents'] = array_values(array_map(fn ($c) => ['title' => $c['title'] ?? '', 'body' => $c['body'] ?? ''], $data['contents'] ?? []));
        $data['status'] = $request->boolean('status');

        return $data;
    }

    private function formData(LandingPage $page): array
    {
        return ['page' => $page, 'products' => Product::query()->where('published', true)->orderBy('name')->get(['id', 'name'])];
    }
}
