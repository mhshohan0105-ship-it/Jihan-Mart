<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'file_original_name', 'file_name', 'extension', 'type', 'file_size', 'width', 'height'])]
class Upload extends Model
{
    protected $appends = ['url', 'size_label'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_name);
    }

    public function getSizeLabelAttribute(): string
    {
        $size = (int) $this->file_size;
        if ($size >= 1048576) {
            return round($size / 1048576, 2).' MB';
        }

        return round($size / 1024, 1).' KB';
    }
}
