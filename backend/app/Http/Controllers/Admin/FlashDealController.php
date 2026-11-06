<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Services\StorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FlashDealController extends Controller
{
    public function index(): View
    {
        return view('admin.marketing.flash-index', ['deals' => FlashDeal::query()->withCount('items')->latest('id')->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.marketing.flash-form', $this->formData(new FlashDeal(['background_color' => '#f47c20', 'text_color' => 'white', 'status' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save(new FlashDeal, $request);

        return redirect()->route('admin.flash_deals.index')->with('success', __('Flash Deal has been inserted successfully'));
    }

    public function edit(FlashDeal $flashDeal): View
    {
        return view('admin.marketing.flash-form', $this->formData($flashDeal->load('items')));
    }

    public function update(Request $request, FlashDeal $flashDeal): RedirectResponse
    {
        $this->save($flashDeal, $request);

        return redirect()->route('admin.flash_deals.index')->with('success', __('Flash Deal has been updated successfully'));
    }

    public function destroy(FlashDeal $flashDeal): JsonResponse
    {
        $flashDeal->delete();
        StorefrontService::flush();

        return response()->json(['success' => true, 'message' => __('Flash Deal has been deleted successfully')]);
    }

    private function save(FlashDeal $deal, Request $request): void
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'background_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['required', 'in:white,dark'],
            'banner_upload_id' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'products' => ['nullable', 'array'],
            'products.*.discount' => ['required', 'numeric', 'min:0'],
            'products.*.discount_type' => ['required', 'in:flat,percent'],
        ]);
        $items = $data['products'] ?? [];
        unset($data['products']);

        DB::transaction(function () use ($deal, $data, $items, $request) {
            $deal->fill($data + ['status' => $request->boolean('status'), 'featured' => $request->boolean('featured')])->save();
            $deal->items()->whereNotIn('product_id', array_keys($items))->delete();
            foreach ($items as $productId => $row) {
                $deal->items()->updateOrCreate(['product_id' => (int) $productId], [
                    'discount' => $row['discount_type'] === 'percent' ? min(100, (float) $row['discount']) : (float) $row['discount'],
                    'discount_type' => $row['discount_type'],
                ]);
            }
            // only one featured deal shows on the home page
            if ($deal->featured) {
                FlashDeal::query()->whereKeyNot($deal->id)->update(['featured' => false]);
            }
        });
        FlashDeal::forgetMap();
        StorefrontService::flush();
    }

    private function formData(FlashDeal $deal): array
    {
        return [
            'deal' => $deal,
            'products' => Product::query()->where('published', true)->orderBy('name')->get(['id', 'name', 'unit_price', 'image_url', 'thumbnail_upload_id']),
            'selected' => $deal->exists ? $deal->items->mapWithKeys(fn ($i) => [$i->product_id => ['discount' => (float) $i->discount, 'discount_type' => $i->discount_type]]) : collect(),
        ];
    }
}
