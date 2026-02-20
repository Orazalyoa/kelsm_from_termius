<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatFile extends Model
{
    protected $fillable = [
        'chat_id',
        'message_id',
        'file_type',
        'file_url',
        'file_name',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * 所属聊天
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * 所属消息
     */
    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /**
     * 上传者
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
     * 获取文件图标
     */
    public function getFileIconAttribute()
    {
        switch ($this->file_type) {
            case 'image':
                return 'fa-file-image';
            case 'video':
                return 'fa-file-video';
            case 'document':
                return 'fa-file-alt';
            default:
                return 'fa-file';
        }
    }
}




