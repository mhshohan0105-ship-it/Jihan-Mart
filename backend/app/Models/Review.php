<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'user_id', 'order_id', 'verified', 'name', 'phone', 'rating', 'comment', 'photo_upload_id', 'helpful_count', 'status', 'admin_reply', 'ip'])]
class Review extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Upload::class, 'photo_upload_id');
    }

    /** Keep products.rating / review_count in sync with approved reviews. */
    protected static function booted(): void
    {
        $sync = function (Review $review) {
            foreach (array_unique(array_filter([$review->product_id, $review->getOriginal('product_id')])) as $id) {
                $approved = self::query()->where('product_id', $id)->where('status', 'approved');
                Product::query()->whereKey($id)->update([
                    'rating' => round((float) $approved->avg('rating'), 2),
                    'review_count' => $approved->count(),
                ]);
            }
        };
        static::saved($sync);
        static::deleted($sync);
    }
}
