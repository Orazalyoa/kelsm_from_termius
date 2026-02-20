<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'image',
        'thumbnail',
        'link',
        'order',
        'is_active',
        'start_date',
        'end_date',
        'target_user_types',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'target_user_types' => 'array',
        'order' => 'integer',
    ];

    protected $appends = ['image_url', 'thumbnail_url'];

    /**
     * Get the image URL attribute.
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Get the thumbnail URL attribute.
     *
     * @return string|null
     */
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : $this->image_url;
    }

    /**
     * Scope active announcements.
     */
    public function scopeActive($query)
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            });
    }

    /**
     * Scope announcements by user type.
     */
    public function scopeForUserType($query, $userType)
    {
        return $query->where(function ($q) use ($userType) {
            $q->whereNull('target_user_types')
                ->orWhereJsonLength('target_user_types', 0)
                ->orWhereJsonContains('target_user_types', $userType);
        });
    }

    /**
     * Scope ordered announcements.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('created_at', 'desc');
    }
}

