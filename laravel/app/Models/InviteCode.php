<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InviteCode extends Model
{
    use HasFactory;

    const USER_TYPE_EXPERT = 'expert';
    const USER_TYPE_COMPANY_ADMIN = 'company_admin';

    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'organization_id', 'created_by', 'user_type', 'permissions',
        'max_uses', 'used_count', 'expires_at', 'status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'permissions' => 'array',
    ];

    /**
     * Get the organization that owns the invite code.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created the invite code.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the uses of the invite code.
     */
    public function uses()
    {
        return $this->hasMany(InviteCodeUse::class);
    }

    /**
     * Check if the invite code is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->used_count < $this->max_uses
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if the invite code is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the invite code has reached max uses.
     *
     * @return bool
     */
    public function isMaxUsesReached()
    {
        return $this->used_count >= $this->max_uses;
    }

    /**
     * Scope a query to only include active invite codes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include valid invite codes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereRaw('used_count < max_uses')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}
