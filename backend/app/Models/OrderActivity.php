<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Audit trail of everything that happens to an order. */
#[Fillable(['order_id', 'user_id', 'action_type', 'title', 'old_value', 'new_value', 'note', 'ip', 'user_agent', 'created_at'])]
class OrderActivity extends Model
{
    public const UPDATED_AT = null;

    /** Timeline dot colour per action type. */
    public const COLORS = [
        'status_change' => 'bg-blue-500',
        'payment_status_change' => 'bg-emerald-500',
        'courier_sent' => 'bg-amber-500',
        'courier_update' => 'bg-amber-400',
        'order_edited' => 'bg-cyan-500',
        'order_created' => 'bg-brand-500',
        'call_logged' => 'bg-green-500',
        'call_note' => 'bg-green-500',
        'note_updated' => 'bg-slate-500',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
