<?php

namespace App\Enums;

class NotificationType
{
    public const CONSULTATION_STATUS = 'consultation_status';
    public const CONSULTATION_ASSIGNMENT = 'consultation_assignment';
    public const CONSULTATION_PRIORITY = 'consultation_priority';
    public const MESSAGE = 'message';
    public const SYSTEM = 'system';
    public const ANNOUNCEMENT = 'announcement';

    /**
     * Get default translation keys for a notification type.
     */
    public static function translationKeys(string $type): array
    {
        switch ($type) {
            case self::CONSULTATION_STATUS:
                return [
                    'title' => 'notifications.templates.consultation_status.title',
                    'body' => 'notifications.templates.consultation_status.body',
                ];
            case self::CONSULTATION_ASSIGNMENT:
                return [
                    'title' => 'notifications.templates.consultation_assignment.title',
                    'body' => 'notifications.templates.consultation_assignment.body',
                ];
            case self::CONSULTATION_PRIORITY:
                return [
                    'title' => 'notifications.templates.consultation_priority.title',
                    'body' => 'notifications.templates.consultation_priority.body',
                ];
            case self::MESSAGE:
                return [
                    'title' => 'notifications.templates.message.title',
                    'body' => 'notifications.templates.message.body',
                ];
            case self::ANNOUNCEMENT:
                return [
                    'title' => null,
                    'body' => null,
                ];
            case self::SYSTEM:
                return [
                    'title' => null,
                    'body' => null,
                ];
            default:
                return [
                    'title' => null,
                    'body' => null,
                ];
        }
    }
}


