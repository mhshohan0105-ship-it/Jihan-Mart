<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'name', 'slug', 'brand_id', 'unit', 'weight', 'min_qty', 'tags', 'suggestion_category_id', 'barcode',
    'thumbnail_upload_id', 'photos', 'image_url', 'video_provider', 'video_link',
    'unit_price', 'sale_price', 'discount', 'discount_type', 'discount_start_date', 'discount_end_date',
    'current_stock', 'sku', 'external_link', 'external_link_btn',
    'variant_product', 'colors', 'choice_options',
    'description', 'pdf_upload_id', 'meta_title', 'meta_description', 'meta_upload_id',
    'payment_policy', 'partial_payment_type', 'partial_advance_amount',
    'published', 'featured', 'todays_deal', 'free_shipping', 'direct_order', 'landing_page', 'shipping_days', 'warranty',
    'num_of_sale', 'legacy_id',
])]
class Product extends Model
{
    use HasUniqueSlug;

    public const PAYMENT_POLICIES = [
        'full_cod' => 'Full Cash On Delivery (100% COD)',
        'partial' => 'Partial Advance + COD',
        'prepaid' => '100% Prepayment Only (No COD)',
    ];

    /** Switches shown as inline toggles on the product list. */
    public const TOGGLES = ['published', 'featured', 'todays_deal', 'free_shipping', 'direct_order', 'landing_page'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'partial_advance_amount' => 'decimal:2',
            'weight' => 'decimal:3',
            'discount_start_date' => 'datetime',
            'discount_end_date' => 'datetime',
            'colors' => 'array',
            'choice_options' => 'array',
            'variant_product' => 'boolean',
            'published' => 'boolean',
            'featured' => 'boolean',
            'todays_deal' => 'boolean',
            'free_shipping' => 'boolean',
            'direct_order' => 'boolean',
            'landing_page' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class)->orderBy('id');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(ProductTier::class)->orderBy('min_qty');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(Upload::class, 'thumbnail_upload_id');
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail?->url ?? $this->image_url;
    }

    public function hasActiveDiscount(): bool
    {
        if ((float) $this->discount <= 0) {
            return false;
        }
        $now = now();

        return (! $this->discount_start_date || $this->discount_start_date <= $now)
            && (! $this->discount_end_date || $this->discount_end_date >= $now);
    }

    /** Price the customer pays for one unit (before tiers), starting from $base (a variant price) or unit_price. */
    public function finalPrice(float|string|null $base = null): float
    {
        $price = (float) ($base ?? $this->unit_price);
        if ($this->sale_price !== null && $base === null && (float) $this->sale_price > 0) {
            $price = (float) $this->sale_price;
        }
        // a running flash deal replaces the product's own discount
        if ($flash = $this->flashDiscount()) {
            [$amount, $type] = $flash;
            $price = $type === 'percent' ? $price - $price * $amount / 100 : $price - $amount;
        } elseif ($this->hasActiveDiscount()) {
            $price = $this->discount_type === 'percent'
                ? $price - $price * (float) $this->discount / 100
                : $price - (float) $this->discount;
        }

        // VAT / TAX (Setup → Vat & TAX) is added on top of the discounted price
        $price = max(0, $price);
        foreach (Tax::productMap()[$this->id] ?? [] as [$amount, $type]) {
            $price += $type === 'percent' ? $price * $amount / 100 : $amount;
        }

        return round($price, 2);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(ProductTax::class);
    }

    /** [discount, type] when this product is in a running flash deal. */
    public function flashDiscount(): ?array
    {
        return FlashDeal::priceMap()[$this->id] ?? null;
    }

    /** Advance the customer must pay for this line under the product's payment policy. */
    public function advanceFor(float $lineTotal): float
    {
        return match ($this->payment_policy) {
            'prepaid' => $lineTotal,
            'partial' => $this->partial_payment_type === 'percent'
                ? round($lineTotal * (float) $this->partial_advance_amount / 100, 2)
                : min($lineTotal, (float) $this->partial_advance_amount),
            default => 0.0,
        };
    }

    public function refreshStockTotal(): void
    {
        $this->forceFill(['current_stock' => (int) $this->stocks()->sum('qty')])->saveQuietly();
    }
}
