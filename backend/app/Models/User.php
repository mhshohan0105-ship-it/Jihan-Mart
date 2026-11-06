<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['type', 'name', 'email', 'phone', 'password', 'avatar_upload_id', 'banned'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const TYPE_ADMIN = 'admin';

    public const TYPE_STAFF = 'staff';

    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_RESELLER = 'reseller';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'banned' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Upload::class, 'avatar_upload_id');
    }

    /** Super admin: bypasses every permission check. */
    public function isSuperAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    /** Can open the admin panel at all. */
    public function canAccessAdmin(): bool
    {
        return in_array($this->type, [self::TYPE_ADMIN, self::TYPE_STAFF], true) && ! $this->banned;
    }
}
