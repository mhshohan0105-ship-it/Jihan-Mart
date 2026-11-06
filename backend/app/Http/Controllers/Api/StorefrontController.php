<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Subscriber;
use App\Services\OrderService;
use App\Services\StorefrontService;
use App\Services\UploadService;
use App\Support\EnvWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Public JSON API used by the storefront (/api/v1). */
class StorefrontController extends Controller
{
    public function __construct(private StorefrontService $store) {}

    public function catalog(): JsonResponse
    {
        return response()->json($this->store->payload()['catalog']);
    }

    public function settings(): JsonResponse
    {
        return response()->json($this->store->payload()['settings']);
    }

    public function locations(): JsonResponse
    {
        return response()->json(['data' => $this->store->payload()['locations']]);
    }

    public function product(string $idOrSlug): JsonResponse
    {
        $p = Product::query()->where('published', true)
            ->where(fn ($q) => $q->where('slug', $idOrSlug)->orWhere('id', ctype_digit($idOrSlug) ? (int) $idOrSlug : 0))
            ->with(['thumbnail', 'stocks', 'tiers', 'categories:id,name,name_bn', 'brand:id,name'])->firstOrFail();

        $photos = array_values(array_filter(array_map(fn ($id) => uploaded_asset($id), explode(',', (string) $p->photos))));

        return response()->json(['data' => [
            'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'brand' => $p->brand?->name,
            'image' => $p->thumbnailUrl(), 'photos' => $photos, 'video' => $p->video_link,
            'price' => $p->finalPrice(), 'unit_price' => (float) $p->unit_price, 'stock' => $p->current_stock, 'min_qty' => $p->min_qty,
            'variants' => $p->variant_product ? $p->stocks->map(fn ($s) => ['variant' => $s->variant, 'price' => $p->finalPrice($s->price), 'qty' => $s->qty, 'image' => uploaded_asset($s->image_upload_id)]) : [],
            'tiers' => $p->tiers->map(fn ($t) => ['min_qty' => $t->min_qty, 'price' => (float) $t->price]),
            'payment_policy' => $p->payment_policy, 'partial_payment_type' => $p->partial_payment_type, 'partial_advance_amount' => (float) $p->partial_advance_amount,
            'free_shipping' => $p->free_shipping, 'direct_order' => $p->direct_order, 'description' => $p->description,
            'categories' => $p->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'bn' => $c->name_bn]),
            'rating' => (float) $p->rating, 'review_count' => $p->review_count,
        ]]);
    }

    public function placeOrder(Request $request, OrderService $orders): JsonResponse
    {
        $this->rejectBots($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'state_id' => ['required', Rule::exists('states', 'id')],
            'city_id' => ['required', Rule::exists('cities', 'id')],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'payment_method' => ['nullable', 'in:cod,bkash,nagad,rocket'],
            'sender_no' => ['nullable', 'string', 'max:20'],
            'transaction_id' => ['nullable', 'string', 'max:60'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'in:website,direct,landing'],
        ]);
        // landing orders are tagged with the domain the request came from, never a value the client sends
        if (($data['source'] ?? null) === 'landing') {
            $data['landing_domain'] = LandingPage::forHost($request->getHost())?->domain;
            $data['source'] = $data['landing_domain'] ? 'landing' : 'website';
        }
        // customers never set prices, discounts or shipping themselves
        $data['items'] = array_map(fn ($i) => ['product_id' => $i['product_id'], 'variant' => $i['variant'] ?? '', 'quantity' => $i['quantity']], $data['items']);
        $data['payment_method'] = $data['payment_method'] ?? 'cod';
        if (! self::paymentEnabled($data['payment_method'])) {
            throw ValidationException::withMessages(['payment_method' => __('This payment method is not available right now.')]);
        }

        $this->checkPaymentPolicy($data);

        $order = $orders->create($data, $data['source'] ?? 'website');

        // the visitor finished, so any captured cart for this phone is converted
        AbandonedCart::query()->where('phone', $order->phone)->where('status', 'pending')->update(['status' => 'converted', 'order_id' => $order->id]);

        return response()->json([
            'success' => true,
            'order' => ['code' => $order->code, 'total' => (float) $order->grand_total, 'shipping' => (float) $order->shipping_cost, 'due' => (float) $order->due],
        ], 201);
    }

    public function validateCoupon(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        return response()->json(['success' => true] + $orders->quoteCoupon($data['code'], $data['items'], $data['phone'] ?? null));
    }

    public function subscribe(Request $request): JsonResponse
    {
        $this->rejectBots($request);
        $data = $request->validate(['contact' => ['required', 'string', 'max:190']]);
        $contact = trim($data['contact']);
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            Subscriber::query()->firstOrCreate(['email' => strtolower($contact)]);
        } elseif ($phone = cleanBdPhone($contact)) {
            Subscriber::query()->firstOrCreate(['phone' => $phone]);
        } else {
            throw ValidationException::withMessages(['contact' => __('Enter a valid email or mobile number.')]);
        }

        return response()->json(['success' => true, 'message' => __('Thanks for subscribing!')], 201);
    }

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20'], 'phone' => ['required', 'string', 'max:20']]);
        $order = Order::query()->where('code', (int) preg_replace('/\D/', '', $data['code']))->where('phone', cleanBdPhone($data['phone']) ?? '-')->first();
        if (! $order) {
            return response()->json(['success' => false, 'message' => __('No order found with this number and phone.')], 404);
        }

        return response()->json(['success' => true, 'data' => [
            'code' => $order->code, 'status' => $order->delivery_status, 'status_label' => Order::DELIVERY_STATUSES[$order->delivery_status],
            'total' => (float) $order->grand_total, 'due' => (float) $order->due, 'tracking_code' => $order->tracking_code,
            'placed_at' => format_date($order->created_at),
        ]]);
    }

    /** Captured as soon as a valid phone is typed at checkout. One pending row per phone. */
    public function abandoned(Request $request): JsonResponse
    {
        $this->rejectBots($request);
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'state_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);
        $phone = cleanBdPhone($data['phone']);
        if (! $phone) {
            return response()->json(['success' => false], 422);
        }
        $names = Product::query()->whereIn('id', array_column($data['items'], 'product_id'))->pluck('name', 'id');
        $items = array_map(fn ($i) => ['product_id' => (int) $i['product_id'], 'variant' => $i['variant'] ?? '', 'quantity' => (int) $i['quantity'], 'name' => $names[$i['product_id']] ?? null], $data['items']);

        AbandonedCart::query()->updateOrCreate(['phone' => $phone, 'status' => 'pending'], [
            'name' => $data['name'] ?? null, 'address' => $data['address'] ?? null,
            'state_id' => $data['state_id'] ?? null, 'city_id' => $data['city_id'] ?? null,
            'items' => $items, 'source' => $request->getHost(),
        ]);

        return response()->json(['success' => true]);
    }

    public function reviews(Request $request): JsonResponse
    {
        $reviews = Review::query()->where('status', 'approved')->with('photo')
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->latest('id')->limit(200)->get();

        return response()->json(['data' => $reviews->map(fn (Review $r) => [
            'id' => $r->id, 'pid' => $r->product_id, 'name' => $r->name, 'rating' => $r->rating, 'text' => $r->comment,
            'photo' => $r->photo?->url, 'helpful' => $r->helpful_count, 'reply' => $r->admin_reply, 'date' => $r->created_at->getTimestampMs(),
            'verified' => $r->verified,
        ])]);
    }

    /** Step 1 of a product review: order number + phone must belong to a delivered order with this product. */
    public function verifyReview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'order_code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ]);
        $order = $this->reviewableOrder((int) $data['product_id'], $data['order_code'], $data['phone']);

        return response()->json(['success' => true, 'name' => $order->name, 'order' => $order->code]);
    }

    public function storeReview(Request $request, UploadService $uploads): JsonResponse
    {
        $this->rejectBots($request);
        $data = $request->validate([
            'product_id' => ['nullable', Rule::exists('products', 'id')],
            'order_code' => ['required_with:product_id', 'nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'phone' => ['required_with:product_id', 'nullable', 'string', 'max:20'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:600'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);
        // product reviews only from people who bought and received that product
        $order = ! empty($data['product_id']) ? $this->reviewableOrder((int) $data['product_id'], $data['order_code'], $data['phone']) : null;

        $photoId = $request->hasFile('photo') ? $uploads->store($request->file('photo'))->id : null;
        $review = Review::query()->create([
            'product_id' => $data['product_id'] ?? null, 'name' => $data['name'],
            'phone' => $order?->phone ?? cleanBdPhone($data['phone'] ?? null),
            'order_id' => $order?->id, 'user_id' => $order?->user_id, 'verified' => (bool) $order,
            'rating' => $data['rating'], 'comment' => $data['comment'], 'photo_upload_id' => $photoId, 'ip' => $request->ip(),
            'status' => setting_on('review_auto_approve') ? 'approved' : 'pending',
        ]);

        return response()->json(['success' => true, 'status' => $review->status], 201);
    }

    public function helpful(Request $request, Review $review): JsonResponse
    {
        abort_unless($review->status === 'approved', 404);
        $request->boolean('undo') ? $review->decrement('helpful_count') : $review->increment('helpful_count');

        return response()->json(['success' => true, 'helpful' => max(0, $review->fresh()->helpful_count)]);
    }

    /** The delivered order (matched by number + phone) that contains this product and has no review for it yet. */
    private function reviewableOrder(int $productId, string $code, string $phone): Order
    {
        $fail = fn (string $msg) => throw ValidationException::withMessages(['order_code' => $msg]);
        $order = Order::query()->where('code', (int) preg_replace('/\D/', '', $code))
            ->where('phone', cleanBdPhone($phone) ?? '-')->first();
        if (! $order) {
            $fail(__('No order found with this order number and phone.'));
        }
        if (! $order->details()->where('product_id', $productId)->exists()) {
            $fail(__('This product is not in that order.'));
        }
        if ($order->delivery_status !== 'delivered') {
            $fail(__('You can review once the order has been delivered.'));
        }
        if (Review::query()->where('order_id', $order->id)->where('product_id', $productId)->exists()) {
            $fail(__('You have already reviewed this product for this order.'));
        }

        return $order;
    }

    /** Hidden "website" field: humans leave it empty, simple bots fill it. */
    private function rejectBots(Request $request): void
    {
        if ($request->filled('website')) {
            throw ValidationException::withMessages(['website' => 'Rejected.']);
        }
        // Setup → Google reCAPTCHA (v3): the storefront sends a token with every write
        if (setting_on('google_recaptcha') && ($secret = EnvWriter::get('RECAPTCHA_SECRET_KEY'))) {
            $ok = false;
            try {
                $res = Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret, 'response' => (string) $request->input('recaptcha_token'), 'remoteip' => $request->ip(),
                ])->json();
                $ok = ($res['success'] ?? false) && ($res['score'] ?? 1) >= 0.5;
            } catch (Throwable $e) {
                report($e);
                $ok = true; // Google unreachable: do not block real customers
            }
            if (! $ok) {
                throw ValidationException::withMessages(['recaptcha_token' => __('Security check failed. Please reload the page and try again.')]);
            }
        }
    }

    /** Setup → Features activation → Payment Related. */
    public static function paymentEnabled(string $method): bool
    {
        return match ($method) {
            'cod' => setting_on('cash_payment', true),
            'bkash', 'nagad', 'rocket' => setting_on($method.'_payment', true),
            default => false,
        };
    }

    /** "100% Prepayment" products need a bKash/Nagad/Rocket payment with a transaction id. */
    private function checkPaymentPolicy(array $data): void
    {
        $prepaid = Product::query()->whereIn('id', array_column($data['items'], 'product_id'))->where('payment_policy', 'prepaid')->exists();
        if ($prepaid && ($data['payment_method'] === 'cod' || empty($data['transaction_id']))) {
            throw ValidationException::withMessages(['payment_method' => __('This order needs advance payment by bKash, Nagad or Rocket with a transaction ID.')]);
        }
    }
}
