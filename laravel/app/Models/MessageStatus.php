<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageStatus extends Model
{
    protected $table = 'message_status';

    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'user_id',
        'status',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    /**
     * 所属消息
     */
    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /**
     * 用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}




