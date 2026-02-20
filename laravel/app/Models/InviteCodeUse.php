<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InviteCodeUse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'invite_code_id', 'user_id', 'used_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * Get the invite code that was used.
     */
    public function inviteCode()
    {
        return $this->belongsTo(InviteCode::class);
    }

    /**
     * Get the user who used the invite code.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
