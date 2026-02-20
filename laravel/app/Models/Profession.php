<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profession extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'name_ru', 'name_kk', 'name_en', 'name_zh',
        'description', 'is_for_expert', 'is_for_lawyer', 'status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_for_expert' => 'boolean',
        'is_for_lawyer' => 'boolean',
    ];

    /**
     * Get the users that have this profession.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_professions')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the localized name based on locale.
     *
     * @param string $locale
     * @return string
     */
    public function getName($locale = 'ru')
    {
        $attribute = 'name_' . $locale;
        return $this->$attribute ?? $this->name_ru;
    }

    /**
     * Scope a query to only include active professions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include professions for experts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForExperts($query)
    {
        return $query->where('is_for_expert', true);
    }

    /**
     * Scope a query to only include professions for lawyers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLawyers($query)
    {
        return $query->where('is_for_lawyer', true);
    }
}
