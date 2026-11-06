<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request, ?string $status = null): View
    {
        $status = $status ?? $request->input('status');
        $base = $this->filtered($request);
        $counts = (clone $base)->reorder()->selectRaw('delivery_status, count(*) as c')->groupBy('delivery_status')->pluck('c', 'delivery_status');

        $orders = $base->when($status && isset(Order::DELIVERY_STATUSES[$status]), fn ($q) => $q->where('delivery_status', $status))
            ->with(['details', 'state', 'city'])->withCount('calls')
            ->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'counts' => $counts,
            'states' => State::query()->where('status', true)->orderBy('id')->get(),
            'confirmedCount' => Order::query()->where('delivery_status', 'confirmed')->count(),
            'title' => $request->input('source') === 'landing' ? __('Landing Page Orders') : __('All Orders'),
        ]);
    }

    /** Landing Page → Landing Page Orders: the order list limited to landing page sources. */
    public function landing(Request $request): View
    {
        $request->merge(['source' => 'landing']);

        return $this->index($request);
    }

    public function show(string $order): View
    {
        $order = Order::findByEncrypted($order);
        if (! $order->viewed) {
            $order->update(['viewed' => true]);
        }

        return view('admin.orders.show', [
            'order' => $order->load(['details.product.thumbnail', 'state', 'city', 'activities.user', 'creator'])->loadCount('calls'),
        ]);
    }

    public function deliveryStatus(Request $request): JsonResponse
    {
        $data = $request->validate(['order_id' => ['required', 'integer'], 'status' => ['required', Rule::in(array_keys(Order::DELIVERY_STATUSES))]]);
        $this->orders->changeDeliveryStatus(Order::query()->findOrFail($data['order_id']), $data['status'], $request->user());

        return response()->json(['success' => true, 'message' => __('Delivery status has been updated')]);
    }

    public function paymentStatus(Request $request): JsonResponse
    {
        $data = $request->validate(['order_id' => ['required', 'integer'], 'status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))]]);
        $this->orders->changePaymentStatus(Order::query()->findOrFail($data['order_id']), $data['status'], $request->user());

        return response()->json(['success' => true, 'message' => __('Payment status has been updated')]);
    }

    public function trackingCode(Request $request): JsonResponse
    {
        $data = $request->validate(['order_id' => ['required', 'integer'], 'tracking_code' => ['nullable', 'string', 'max:60']]);
        $this->orders->updateTrackingCode(Order::query()->findOrFail($data['order_id']), $data['tracking_code'] ?? null, $request->user());

        return response()->json(['success' => true, 'message' => __('Tracking code saved')]);
    }

    public function note(Request $request): JsonResponse
    {
        $data = $request->validate(['order_id' => ['required', 'integer'], 'admin_note' => ['nullable', 'string', 'max:2000']]);
        $this->orders->updateNote(Order::query()->findOrFail($data['order_id']), $data['admin_note'] ?? null, $request->user());

        return response()->json(['success' => true, 'message' => __('Comment saved')]);
    }

    public function logCall(Request $request): JsonResponse
    {
        $order = Order::query()->findOrFail($request->integer('order_id'));
        $total = $this->orders->logCall($order, $request->user());

        return response()->json(['success' => true, 'total_calls' => $total]);
    }

    public function callNote(Request $request): JsonResponse
    {
        $data = $request->validate(['order_id' => ['required', 'integer'], 'note' => ['required', 'string', 'max:500']]);
        $total = $this->orders->logCall(Order::query()->findOrFail($data['order_id']), $request->user(), $data['note']);

        return response()->json(['success' => true, 'message' => __('Note saved'), 'total_calls' => $total]);
    }

    /** Call history modal. */
    public function calls(Order $order): JsonResponse
    {
        $calls = $order->calls()->with('user:id,name')->get();

        return response()->json([
            'order' => ['code' => $order->code, 'name' => $order->name, 'phone' => $order->phone],
            'total' => $calls->count(),
            'breakdown' => $calls->groupBy(fn ($c) => $c->user?->name ?? __('System'))->map->count(),
            'calls' => $calls->map(fn ($c) => ['by' => $c->user?->name ?? '—', 'phone' => $c->phone, 'at' => format_date($c->created_at), 'note' => $c->note]),
        ]);
    }

    /** Activity log modal. */
    public function activities(Order $order): JsonResponse
    {
        return response()->json(['data' => $order->activities()->with('user:id,name')->get()->map(fn ($a) => [
            'type' => $a->action_type, 'title' => $a->title, 'old' => $a->old_value, 'new' => $a->new_value, 'note' => $a->note,
            'by' => $a->user?->name ?? __('System'), 'at' => format_date($a->created_at),
        ])]);
    }

    public function destroy(Order $order): JsonResponse
    {
        if (! $order->stock_restored) {
            $this->orders->changeDeliveryStatus($order, 'cancelled', request()->user());
        }
        $order->delete();

        return response()->json(['success' => true, 'message' => __('Order has been deleted')]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $orders = Order::query()->whereIn('id', array_map('intval', (array) $request->input('ids', [])))->get();
        foreach ($orders as $order) {
            if (! $order->stock_restored) {
                $this->orders->changeDeliveryStatus($order, 'cancelled', $request->user());
            }
            $order->delete();
        }

        return response()->json(['success' => true, 'message' => __(':count orders deleted', ['count' => $orders->count()])]);
    }

    // ---------------- POS create / edit ----------------

    public function create(): View
    {
        return view('admin.orders.pos', $this->posData(null));
    }

    public function store(Request $request): JsonResponse
    {
        $order = $this->orders->create($this->posValidated($request), 'pos', $request->user());

        return response()->json(['success' => true, 'message' => __('Order #:code created', ['code' => $order->code]), 'redirect' => route('admin.orders.show', $order->encryptedId())]);
    }

    public function edit(Order $order): View
    {
        return view('admin.orders.pos', $this->posData($order->load('details.product.stocks')));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->orders->update($order, $this->posValidated($request), $request->user());

        return response()->json(['success' => true, 'message' => __('Order #:code updated', ['code' => $order->code]), 'redirect' => route('admin.orders.show', $order->encryptedId())]);
    }

    /** POS product grid. */
    public function posProducts(Request $request): JsonResponse
    {
        $q = Product::query()->where('published', true)->with(['stocks', 'thumbnail']);
        if ($search = trim((string) $request->input('search'))) {
            $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->orWhere('barcode', $search)
                ->orWhereHas('stocks', fn ($s) => $s->where('sku', 'like', "%{$search}%")));
        }
        if ($category = $request->integer('category')) {
            $ids = Category::query()->find($category)?->descendantIds() ?? [$category];
            $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids));
        }
        if ($brand = $request->integer('brand')) {
            $q->where('brand_id', $brand);
        }
        $page = $q->orderByDesc('num_of_sale')->paginate(24);

        return response()->json([
            'last_page' => $page->lastPage(),
            'data' => collect($page->items())->map(fn (Product $p) => [
                'id' => $p->id, 'name' => $p->name, 'image' => $p->thumbnailUrl(), 'stock' => $p->current_stock,
                'price' => $p->finalPrice(), 'variant_product' => $p->variant_product,
                'stocks' => $p->stocks->map(fn ($s) => ['variant' => $s->variant, 'qty' => $s->qty, 'price' => $p->finalPrice($p->variant_product ? $s->price : null)]),
            ]),
        ]);
    }

    public function customerSearch(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));
        $customers = User::query()->where('type', User::TYPE_CUSTOMER)
            ->when($term, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', '%'.(cleanBdPhone($term) ?? $term).'%')))
            ->latest('id')->limit(20)->get(['id', 'name', 'phone', 'email']);

        // last address used, to prefill the POS form
        $last = Order::query()->whereIn('user_id', $customers->pluck('id'))->latest('id')->get(['user_id', 'address', 'state_id', 'city_id'])->unique('user_id')->keyBy('user_id');

        return response()->json(['data' => $customers->map(fn ($c) => [
            'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'email' => $c->email,
            'address' => $last[$c->id]->address ?? '', 'state_id' => $last[$c->id]->state_id ?? null, 'city_id' => $last[$c->id]->city_id ?? null,
        ])]);
    }

    private function posData(?Order $order): array
    {
        return [
            'order' => $order,
            'categories' => Category::tree(),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'states' => State::query()->where('status', true)->with(['cities' => fn ($q) => $q->where('status', true)])->get(),
        ];
    }

    private function posValidated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['required', 'string', 'max:500'],
            'state_id' => ['nullable', Rule::exists('states', 'id')],
            'city_id' => ['nullable', Rule::exists('cities', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:flat,percent'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'advance_method' => ['nullable', 'in:cod,bkash,nagad,rocket'],
            'sender_no' => ['nullable', 'string', 'max:20'],
            'transaction_id' => ['nullable', 'string', 'max:60'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function filtered(Request $request): Builder
    {
        $q = Order::query()->latest('id');
        if ($search = trim((string) $request->input('search'))) {
            $phone = cleanBdPhone($search);
            $q->where(fn ($q) => $q->where('code', ltrim($search, '#'))->orWhere('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', '%'.($phone ?? $search).'%')->orWhere('email', 'like', "%{$search}%"));
        }
        if ($request->filled('date_from')) {
            $q->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        if ($request->filled('date_to')) {
            $q->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }
        if ($request->filled('payment_status')) {
            $q->where('payment_status', $request->payment_status);
        }
        if ($request->filled('state_id')) {
            $q->where('state_id', $request->integer('state_id'));
        }
        if ($request->filled('city_id')) {
            $q->where('city_id', $request->integer('city_id'));
        }
        if ($request->filled('source')) {
            $q->where('source', $request->input('source'));
        }
        if ($request->filled('landing_domain')) {
            $q->where('landing_domain', $request->input('landing_domain'));
        }

        return $q;
    }
}
