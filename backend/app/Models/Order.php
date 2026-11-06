<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'code', 'user_id', 'created_by', 'name', 'phone', 'email', 'address', 'state_id', 'city_id', 'source', 'landing_domain',
    'payment_method', 'payment_status', 'delivery_status', 'subtotal', 'shipping_cost', 'discount', 'coupon_discount', 'coupon_code',
    'advance_amount', 'advance_method', 'sender_no', 'transaction_id', 'grand_total', 'due',
    'tracking_code', 'courier', 'consignment_id', 'admin_note', 'customer_note', 'viewed', 'stock_restored',
])]
class Order extends Model
{
    /** Delivery statuses in display order => English label. */
    public const DELIVERY_STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'picked_up' => 'Picked Up',
        'on_the_way' => 'On The Way',
        'delivered' => 'Delivered',
        'phone_off' => 'Phone Off',
        'not_receive_call' => 'Not Receive Call',
        'returned' => 'Returned',
        'cancelled' => 'Cancel',
    ];

    /** Statuses that put the items back on the shelf. */
    public const RESTOCK_STATUSES = ['cancelled', 'returned'];

    public const PAYMENT_STATUSES = ['unpaid' => 'Un-Paid', 'partial_paid' => 'Partial Paid', 'paid' => 'Paid'];

    public const PAYMENT_METHODS = ['cod' => 'Cash on Delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'card' => 'Card'];

    public const SOURCES = ['website' => 'Website', 'direct' => 'Product page', 'landing' => 'Landing page', 'pos' => 'Admin (POS)', 'abandoned' => 'Abandoned cart'];

    /** Tailwind classes per delivery status badge. */
    public const STATUS_COLORS = [
        'pending' => 'bg-amber-100 text-amber-800',
        'confirmed' => 'bg-sky-100 text-sky-800',
        'picked_up' => 'bg-indigo-100 text-indigo-800',
        'on_the_way' => 'bg-violet-100 text-violet-800',
        'delivered' => 'bg-emerald-100 text-emerald-800',
        'phone_off' => 'bg-slate-200 text-slate-700',
        'not_receive_call' => 'bg-orange-100 text-orange-800',
        'returned' => 'bg-rose-100 text-rose-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'discount' => 'decimal:2', 'coupon_discount' => 'decimal:2',
            'advance_amount' => 'decimal:2', 'grand_total' => 'decimal:2', 'due' => 'decimal:2',
            'viewed' => 'boolean', 'stock_restored' => 'boolean',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest('id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(OrderCallLog::class)->latest('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** Public links never expose the numeric id. */
    public function encryptedId(): string
    {
        return Crypt::encryptString((string) $this->id);
    }

    public static function findByEncrypted(string $value): self
    {
        try {
            $id = (int) Crypt::decryptString($value);
        } catch (\Throwable) {
            abort(404);
        }

        return self::query()->findOrFail($id);
    }

    public function areaLabel(): string
    {
        return collect([$this->state?->name, $this->city?->name])->filter()->implode(' / ');
    }

    public function itemCount(): int
    {
        return (int) $this->details->sum('quantity');
    }
}
