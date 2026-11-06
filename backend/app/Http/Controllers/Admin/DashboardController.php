<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use App\Support\AdminMenu;
use App\Support\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $range = DateRange::fromRequest($request);
        [$dayLabels, $dayValues] = $reports->daily(7);

        return view('admin.dashboard', [
            'range' => $range,
            'kpi' => $reports->dashboard($range),
            'tiles' => [
                'customers' => User::query()->where('type', User::TYPE_CUSTOMER)->count(),
                'products' => Product::query()->where('published', true)->count(),
                'categories' => Category::query()->count(),
                'low_stock' => Product::query()->where('current_stock', '<=', (int) get_setting('low_stock_threshold', 5))->count(),
            ],
            'charts' => [
                'monthly' => $reports->monthly((int) now()->year),
                'daily' => ['labels' => $dayLabels, 'values' => $dayValues],
                'categorySales' => $reports->categorySales($range)->take(8),
                'categoryStock' => $reports->categoryStock()->take(10),
            ],
            'recent' => Order::query()->latest('id')->limit(8)->get(),
            'lowStock' => $reports->lowStock(8),
            'topProducts' => $reports->topProducts(8),
        ]);
    }

    /** "Realtime" tile: orders in the last 30 minutes (polled by the dashboard). */
    public function realtime(): JsonResponse
    {
        $since = now()->subMinutes(30);

        return response()->json([
            'orders' => Order::query()->where('created_at', '>=', $since)->count(),
            'amount' => (float) Order::query()->where('created_at', '>=', $since)->sum('grand_total'),
            'at' => now()->format('h:i:s A'),
        ]);
    }

    /** Placeholder for sidebar pages that later phases build. */
    public function soon(string $page): View
    {
        $item = AdminMenu::soonPage($page);
        abort_unless($item, 404);
        abort_unless(auth()->user()->can($item['permission']), 403);

        return view('admin.soon', ['item' => $item]);
    }
}
