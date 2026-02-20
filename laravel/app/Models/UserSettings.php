<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_settings',
        'privacy_settings',
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'privacy_settings' => 'array',
    ];

    /**
     * Get default notification settings
     */
    public static function defaultNotificationSettings(): array
    {
        return [
            'email_notifications' => true,
            'push_notifications' => true,
            'sms_notifications' => false,
            'consultation_updates' => true,
            'chat_messages' => true,
            'invite_code_used' => true,
        ];
    }

    /**
     * Get default privacy settings
     */
    public static function defaultPrivacySettings(): array
    {
        return [
            'profile_visibility' => 'organization', // public, organization, private
            'show_email' => false,
            'show_phone' => false,
            'allow_messages' => true,
        ];
    }

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

