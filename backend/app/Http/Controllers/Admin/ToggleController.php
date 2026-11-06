<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\FlashDeal;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Inline switches on list pages ("Published", "Featured"...) save here instantly by AJAX. */
class ToggleController extends Controller
{
    /** resource => [model, allowed fields, permission] */
    private const MAP = [
        'products' => [Product::class, Product::TOGGLES, 'edit_products'],
        'categories' => [Category::class, ['featured', 'megamenu', 'single_grid'], 'edit_categories'],
        'collections' => [Collection::class, ['status'], 'edit_collections'],
        'flash_deals' => [FlashDeal::class, ['status', 'featured'], 'edit_flash_deals'],
        'coupons' => [Coupon::class, ['status'], 'edit_coupons'],
        'landing_pages' => [LandingPage::class, ['status'], 'edit_landing_pages'],
        'taxes' => [Tax::class, ['status'], 'manage_taxes'],
    ];

    public function __invoke(Request $request, string $resource, int $id): JsonResponse
    {
        abort_unless(isset(self::MAP[$resource]), 404);
        [$model, $fields, $permission] = self::MAP[$resource];
        abort_unless($request->user()->can($permission), 403);

        $data = $request->validate([
            'field' => ['required', 'in:'.implode(',', $fields)],
            'value' => ['required', 'boolean'],
        ]);

        $record = $model::query()->findOrFail($id);
        $record->update([$data['field'] => $request->boolean('value')]);

        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }
}
