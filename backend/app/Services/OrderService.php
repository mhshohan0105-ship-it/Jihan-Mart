<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Order lifecycle: create (deducts stock), edit items, status changes (restock on cancel/return),
 * payment, notes, calls. Every change is written to order_activities.
 */
class OrderService
{
    public function __construct(private ShippingService $shipping, private CouponService $coupons) {}

    /**
     * @param  array  $data  name, phone, address, state_id, city_id, items[{product_id, variant, quantity, price?}],
     *                       discount, discount_type, shipping_cost (null = calculate), advance_amount, advance_method,
     *                       sender_no, transaction_id, customer_note, admin_note, payment_method, landing_domain
     */
    public function create(array $data, string $source = 'website', ?User $staff = null): Order
    {
        $phone = $this->phone($data['phone'] ?? null);

        return DB::transaction(function () use ($data, $source, $staff, $phone) {
            $lines = $this->priceLines($data['items'] ?? [], $source === 'pos');
            $this->checkMinimum($lines, $source);
            $this->takeStock($lines);

            $order = new Order([
                'code' => $this->nextCode(),
                'user_id' => $this->customer($data['name'], $phone, $data['email'] ?? null)->id,
                'created_by' => $staff?->id,
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'state_id' => $data['state_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'source' => $source,
                'landing_domain' => $data['landing_domain'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cod',
                'delivery_status' => 'pending',
                'advance_method' => $data['advance_method'] ?? null,
                'sender_no' => $data['sender_no'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
                'viewed' => $source === 'pos',
            ]);
            $coupon = null;
            if (! empty($data['coupon_code'])) {
                ['coupon' => $coupon, 'discount' => $couponDiscount] = $this->coupons->apply($data['coupon_code'], $lines, $phone);
                $order->coupon_code = $coupon->code;
                $order->coupon_discount = $couponDiscount;
            }
            $this->applyTotals($order, $lines, $data);
            $order->save();
            $order->details()->createMany(array_map(fn ($l) => collect($l)->except(['product', 'stock'])->all(), $lines));
            if ($coupon) {
                $this->coupons->recordUse($coupon, $order->id, $order->user_id, $phone, (float) $order->coupon_discount);
            }

            $this->log($order, 'order_created', __('Order placed'), null, Order::SOURCES[$source] ?? $source, $coupon ? 'Coupon '.$coupon->code : null, $staff);

            return $order->load('details');
        });
    }

    /** POS edit: put the old items back, then take the new ones. */
    public function update(Order $order, array $data, User $staff): Order
    {
        $phone = $this->phone($data['phone'] ?? null);

        return DB::transaction(function () use ($order, $data, $staff, $phone) {
            if (! $order->stock_restored) {
                $this->restock($order);
            }
            $order->details()->delete();

            $lines = $this->priceLines($data['items'] ?? [], true);
            $this->takeStock($lines);
            $order->fill(collect($data)->only(['name', 'email', 'address', 'state_id', 'city_id', 'advance_method', 'sender_no', 'transaction_id', 'customer_note', 'payment_method'])->all());
            $order->phone = $phone;
            $order->stock_restored = false;
            $this->applyTotals($order, $lines, $data);
            $order->save();
            $order->details()->createMany(array_map(fn ($l) => collect($l)->except(['product', 'stock'])->all(), $lines));

            $this->log($order, 'order_edited', __('Order edited'), null, format_price($order->grand_total), null, $staff);

            return $order->load('details');
        });
    }

    public function changeDeliveryStatus(Order $order, string $status, ?User $staff = null): void
    {
        abort_unless(array_key_exists($status, Order::DELIVERY_STATUSES), 422);
        if ($order->delivery_status === $status) {
            return;
        }

        DB::transaction(function () use ($order, $status, $staff) {
            $old = $order->delivery_status;
            $restock = in_array($status, Order::RESTOCK_STATUSES, true);

            if ($restock && ! $order->stock_restored) {
                $this->restock($order);
                $order->stock_restored = true;
            } elseif (! $restock && $order->stock_restored) {
                // back from cancelled/returned: the items leave the shelf again
                $this->takeStock($order->details->map(fn ($d) => ['product_id' => $d->product_id, 'variant' => $d->variant, 'quantity' => $d->quantity])->all());
                $order->stock_restored = false;
            }

            $order->delivery_status = $status;
            $order->save();
            $this->log($order, 'status_change', __('Delivery status changed'), Order::DELIVERY_STATUSES[$old], Order::DELIVERY_STATUSES[$status], null, $staff);
        });
    }

    public function changePaymentStatus(Order $order, string $status, ?User $staff = null): void
    {
        abort_unless(array_key_exists($status, Order::PAYMENT_STATUSES), 422);
        $old = $order->payment_status;
        $order->update(['payment_status' => $status, 'due' => $status === 'paid' ? 0 : max(0, (float) $order->grand_total - (float) $order->advance_amount)]);
        $this->log($order, 'payment_status_change', __('Payment status changed'), Order::PAYMENT_STATUSES[$old], Order::PAYMENT_STATUSES[$status], null, $staff);
    }

    public function updateNote(Order $order, ?string $note, ?User $staff = null): void
    {
        $old = $order->admin_note;
        $order->update(['admin_note' => $note]);
        $this->log($order, 'note_updated', __('Comment updated'), $old, $note, null, $staff);
    }

    public function updateTrackingCode(Order $order, ?string $code, ?User $staff = null): void
    {
        $old = $order->tracking_code;
        $order->update(['tracking_code' => $code]);
        $this->log($order, 'order_edited', __('Tracking code updated'), $old, $code, null, $staff);
    }

    public function logCall(Order $order, ?User $staff, ?string $note = null): int
    {
        $order->calls()->create(['user_id' => $staff?->id, 'phone' => $order->phone, 'call_status' => 'called', 'note' => $note, 'created_at' => now()]);
        $this->log($order, $note ? 'call_note' : 'call_logged', $note ? __('Call note added') : __('Customer called'), null, null, $note, $staff);

        return $order->calls()->count();
    }

    public function log(Order $order, string $type, string $title, ?string $old = null, ?string $new = null, ?string $note = null, ?User $staff = null): void
    {
        $request = request();
        OrderActivity::query()->create([
            'order_id' => $order->id,
            'user_id' => $staff?->id ?? auth()->id(),
            'action_type' => $type,
            'title' => $title,
            'old_value' => $old !== null ? mb_substr($old, 0, 250) : null,
            'new_value' => $new !== null ? mb_substr($new, 0, 250) : null,
            'note' => $note,
            'ip' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 250),
            'created_at' => now(),
        ]);
    }

    /** Storefront "apply coupon": prices the cart the same way an order would and returns the discount. */
    public function quoteCoupon(string $code, array $items, ?string $phone = null): array
    {
        $lines = $this->priceLines($items, false);
        ['coupon' => $coupon, 'discount' => $discount] = $this->coupons->apply($code, $lines, $phone ? cleanBdPhone($phone) : null);

        return ['code' => $coupon->code, 'discount' => $discount, 'subtotal' => round(array_sum(array_column($lines, 'total')), 2)];
    }

    /** Setup → Order Configuration: customer orders below the minimum product total are refused (POS is not limited). */
    private function checkMinimum(array $lines, string $source): void
    {
        $min = (float) get_setting('minimum_order_amount', 0);
        if ($source !== 'pos' && $min > 0 && setting_on('minimum_order_amount_check') && array_sum(array_column($lines, 'total')) < $min) {
            throw ValidationException::withMessages(['items' => __('Minimum order amount is :amount.', ['amount' => format_price($min)])]);
        }
    }

    /** Resolve products/variants into priced lines. POS may override the unit price. */
    private function priceLines(array $items, bool $allowPriceOverride): array
    {
        $items = array_values(array_filter($items, fn ($i) => ! empty($i['product_id']) && (int) ($i['quantity'] ?? 0) > 0));
        if (! $items) {
            throw ValidationException::withMessages(['items' => __('Add at least one product.')]);
        }

        $products = Product::query()->with(['stocks', 'tiers'])->whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');
        $lines = [];
        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);
            if (! $product) {
                throw ValidationException::withMessages(['items' => __('A product in the cart no longer exists.')]);
            }
            $variant = (string) ($item['variant'] ?? '');
            $stock = $product->stocks->firstWhere('variant', $variant);
            if (! $stock) {
                throw ValidationException::withMessages(['items' => __(':name: choose a valid variant.', ['name' => $product->name])]);
            }
            $qty = max((int) $item['quantity'], 1);

            $price = $product->finalPrice($product->variant_product ? $stock->price : null);
            $tier = $product->tiers->where('min_qty', '<=', $qty)->sortByDesc('min_qty')->first();
            if ($tier) {
                $price = min($price, (float) $tier->price);
            }
            if ($allowPriceOverride && isset($item['price']) && $item['price'] !== '') {
                $price = max(0, (float) $item['price']);
            }

            $lines[] = [
                'product' => $product,
                'stock' => $stock,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variant' => $variant,
                'sku' => $stock->sku ?: $product->sku,
                'price' => round($price, 2),
                'quantity' => $qty,
                'total' => round($price * $qty, 2),
            ];
        }

        return $lines;
    }

    private function applyTotals(Order $order, array $lines, array $data): void
    {
        $subtotal = round(array_sum(array_column($lines, 'total')), 2);
        $allFree = collect($lines)->every(fn ($l) => isset($l['product']) && $l['product']->free_shipping);
        $shipping = isset($data['shipping_cost']) && $data['shipping_cost'] !== '' && $data['shipping_cost'] !== null
            ? (float) $data['shipping_cost']
            : $this->shipping->cost($data['city_id'] ?? null, $subtotal, $allFree);

        $discount = (float) ($data['discount'] ?? 0);
        if (($data['discount_type'] ?? 'flat') === 'percent') {
            $discount = round($subtotal * min($discount, 100) / 100, 2);
        }
        $discount = min($discount, $subtotal);

        $grand = round($subtotal + $shipping - $discount - (float) $order->coupon_discount, 2);
        $advance = min(max(0, (float) ($data['advance_amount'] ?? 0)), $grand);

        $order->fill([
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'discount' => $discount,
            'grand_total' => $grand,
            'advance_amount' => $advance,
            'due' => round($grand - $advance, 2),
            'payment_status' => $advance <= 0 ? 'unpaid' : ($advance >= $grand ? 'paid' : 'partial_paid'),
        ]);
    }

    /** Deduct stock; refuse when there is not enough (stock never silently goes negative). */
    private function takeStock(array $lines): void
    {
        $touched = [];
        foreach ($lines as $line) {
            $stock = ProductStock::query()->where('product_id', $line['product_id'])->where('variant', $line['variant'] ?? '')->lockForUpdate()->first();
            if (! $stock || $stock->qty < $line['quantity']) {
                $name = $line['product_name'] ?? Product::query()->find($line['product_id'])?->name;
                throw ValidationException::withMessages(['items' => __(':name: only :qty in stock.', ['name' => trim($name.' '.($line['variant'] ?? '')), 'qty' => max(0, (int) $stock?->qty)])]);
            }
            $stock->decrement('qty', $line['quantity']);
            Product::query()->whereKey($line['product_id'])->increment('num_of_sale', $line['quantity']);
            $touched[$line['product_id']] = true;
        }
        $this->refreshTotals(array_keys($touched));
    }

    private function restock(Order $order): void
    {
        $touched = [];
        foreach ($order->details as $detail) {
            if (! $detail->product_id) {
                continue;
            }
            ProductStock::query()->where('product_id', $detail->product_id)->where('variant', $detail->variant)->increment('qty', $detail->quantity);
            Product::query()->whereKey($detail->product_id)->where('num_of_sale', '>=', $detail->quantity)->decrement('num_of_sale', $detail->quantity);
            $touched[$detail->product_id] = true;
        }
        $this->refreshTotals(array_keys($touched));
    }

    private function refreshTotals(array $productIds): void
    {
        StorefrontService::flush(); // stock shown on the storefront changed
        foreach (Product::query()->whereIn('id', $productIds)->get() as $product) {
            $product->refreshStockTotal();
        }
    }

    private function nextCode(): int
    {
        $last = (int) Order::query()->lockForUpdate()->max('code');

        return max($last + 1, 1001);
    }

    /** Guest orders create (or reuse) a customer account keyed by phone. */
    private function customer(string $name, string $phone, ?string $email): User
    {
        return User::query()->where('phone', $phone)->where('type', User::TYPE_CUSTOMER)->first()
            ?? User::query()->create(['type' => User::TYPE_CUSTOMER, 'name' => $name, 'phone' => $phone, 'email' => $email ?: null, 'password' => str()->random(32)]);
    }

    private function phone(?string $phone): string
    {
        $clean = cleanBdPhone($phone);
        if (! $clean) {
            throw ValidationException::withMessages(['phone' => __('Enter a valid Bangladeshi mobile number (01XXXXXXXXX).')]);
        }

        return $clean;
    }
}
