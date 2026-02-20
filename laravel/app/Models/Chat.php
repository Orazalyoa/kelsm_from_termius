<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 聊天创建者
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 聊天参与者
     */
    public function participants()
    {
        return $this->hasMany(ChatParticipant::class, 'chat_id');
    }

    /**
     * 聊天参与的用户
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_participants', 'chat_id', 'user_id')
            ->withPivot('role', 'joined_at', 'last_read_at')
            ->withTimestamps();
    }

    /**
     * 聊天消息
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_id');
    }

    /**
     * 聊天文件
     */
    public function files()
    {
        return $this->hasMany(ChatFile::class, 'chat_id');
    }

    /**
     * 最后一条消息
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'chat_id')->latest();
    }

    /**
     * 获取参与者数量
     */
    public function getParticipantsCountAttribute()
    {
        return $this->participants()->count();
    }

    /**
     * 获取消息数量
     */
    public function getMessagesCountAttribute()
    {
        return $this->messages()->count();
    }

    /**
     * Check if chat is active.
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Set chat as inactive (read-only).
     */
    public function setInactive()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Set chat as active.
     */
    public function setActive()
    {
        $this->update(['is_active' => true]);
    }
}





