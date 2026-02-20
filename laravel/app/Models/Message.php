<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chat_id',
        'sender_id',
        'type',
        'content',
        'file_url',
        'file_name',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 所属聊天
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * 发送者
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * 消息状态
     */
    public function statuses()
    {
        return $this->hasMany(MessageStatus::class, 'message_id');
    }

    /**
     * 获取文件大小（格式化）
     */
    public function getFileSizeFormattedAttribute()
    {
        if (!$this->file_size) return null;
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * 是否为系统消息
     */
    public function isSystemMessage()
    {
        return $this->type === 'system';
    }

    /**
     * 是否为文件消息
     */
    public function isFileMessage()
    {
        return in_array($this->type, ['document', 'image', 'video']);
    }
}




