<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * 创建通知
     *
     * @param User|int $user 用户或用户ID
     * @param string $type 通知类型
     * @param string $title 通知标题
     * @param string $content 通知内容
     * @param array $data 额外数据
     * @return Notification
     */
    public function createNotification($user, string $type, string $title, string $content, array $data = []): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'data' => $data,
        ]);

        // Send push notification
        $this->sendPushNotification($userId, $title, $content, $data);

        return $notification;
    }

    /**
     * Send push notification via Firebase Cloud Messaging
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    protected function sendPushNotification(int $userId, string $title, string $body, array $data = []): void
    {
        $fcmServerKey = env('FCM_SERVER_KEY');
        
        if (empty($fcmServerKey)) {
            Log::warning('FCM_SERVER_KEY not configured, skipping push notification');
            return;
        }

        try {
            // Prepare payload
            $data = $this->preparePushData($data);

            // Get user device tokens from chat service database
            $deviceTokens = $this->getUserDeviceTokens($userId);
            
            if (empty($deviceTokens)) {
                Log::info("User {$userId} has no active device tokens");
                return;
            }

            // Send notification to each device
            foreach ($deviceTokens as $token) {
                $this->sendFCMNotification($token, $title, $body, $data, $fcmServerKey);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }

    /**
     * Get user device tokens from chat service database
     *
     * @param int $userId
     * @return array
     */
    protected function getUserDeviceTokens(int $userId): array
    {
        try {
            // Query device_tokens table directly
            $tokens = \DB::connection('mysql')
                ->table('device_tokens')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();
            
            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to fetch device tokens: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Send FCM notification
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $serverKey
     * @return void
     */
    protected function sendFCMNotification(string $token, string $title, string $body, array $data, string $serverKey): void
    {
        try {
            // Convert all data values to strings (FCM requirement)
            $stringData = [];
            foreach ($data as $key => $value) {
                $stringData[$key] = (string) $value;
            }

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'priority' => 'high',
                'content_available' => true,
            ]);

            if ($response->successful()) {
                Log::info("Push notification sent successfully to token: " . substr($token, 0, 10) . '...');
            } else {
                Log::error("FCM error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification: ' . $e->getMessage());
        }
    }

    /**
     * 创建咨询状态变更通知
     *
     * @param User|int $user 用户
     * @param int $consultationId 咨询ID
     * @param string $oldStatus 旧状态
     * @param string $newStatus 新状态
     * @return Notification
     */
    public function notifyConsultationStatusChange($user, int $consultationId, string $oldStatus, string $newStatus): Notification
    {
        $statusLabels = [
            'pending' => '待处理',
            'in_progress' => '处理中',
            'archived' => '已归档',
            'cancelled' => '已取消',
        ];

        $title = '咨询状态变更';
        $content = sprintf(
            '您的咨询状态已从「%s」变更为「%s」',
            $statusLabels[$oldStatus] ?? $oldStatus,
            $statusLabels[$newStatus] ?? $newStatus
        );

        $data = $this->injectTranslationMeta(
            NotificationType::CONSULTATION_STATUS,
            [
                'consultation_id' => $consultationId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            [
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
            ]
        );

        return $this->createNotification(
            $user,
            NotificationType::CONSULTATION_STATUS,
            $title,
            $content,
            $data
        );
    }

    /**
     * 创建咨询分配通知（律师被分配咨询）
     *
     * @param User|int $lawyer 律师
     * @param int $consultationId 咨询ID
     * @param string $consultationTitle 咨询标题
     * @return Notification
     */
    public function notifyConsultationAssigned($lawyer, int $consultationId, string $consultationTitle): Notification
    {
        $title = '新咨询分配';
        $content = sprintf('您收到了一个新的咨询：%s', $consultationTitle);

        $data = $this->injectTranslationMeta(
            NotificationType::CONSULTATION_ASSIGNMENT,
            [
                'consultation_id' => $consultationId,
                'consultation_title' => $consultationTitle,
            ],
            [
                'consultationTitle' => $consultationTitle,
            ]
        );

        return $this->createNotification(
            $lawyer,
            NotificationType::CONSULTATION_ASSIGNMENT,
            $title,
            $content,
            $data
        );
    }

    /**
     * 创建优先级提升通知
     *
     * @param User|int $lawyer 律师
     * @param int $consultationId 咨询ID
     * @param string $oldPriority 旧优先级
     * @param string $newPriority 新优先级
     * @return Notification
     */
    public function notifyPriorityEscalated($lawyer, int $consultationId, string $oldPriority, string $newPriority): Notification
    {
        $priorityLabels = [
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急',
        ];

        $title = '咨询优先级提升';
        $content = sprintf(
            '咨询优先级已从「%s」提升至「%s」',
            $priorityLabels[$oldPriority] ?? $oldPriority,
            $priorityLabels[$newPriority] ?? $newPriority
        );

        $data = $this->injectTranslationMeta(
            NotificationType::CONSULTATION_PRIORITY,
            [
                'consultation_id' => $consultationId,
                'old_priority' => $oldPriority,
                'new_priority' => $newPriority,
            ],
            [
                'oldPriority' => $oldPriority,
                'newPriority' => $newPriority,
            ]
        );

        return $this->createNotification(
            $lawyer,
            NotificationType::CONSULTATION_PRIORITY,
            $title,
            $content,
            $data
        );
    }


    /**
     * 创建消息通知
     *
     * @param User|int $user 用户
     * @param int $chatId 聊天ID
     * @param string $senderName 发送者名称
     * @param string $messagePreview 消息预览
     * @return Notification
     */
    public function notifyNewMessage($user, int $chatId, string $senderName, string $messagePreview): Notification
    {
        $title = '新消息';
        $content = sprintf('%s: %s', $senderName, $messagePreview);

        $data = $this->injectTranslationMeta(
            NotificationType::MESSAGE,
            [
                'chat_id' => $chatId,
                'sender_name' => $senderName,
                'message_preview' => $messagePreview,
            ],
            [
                'sender' => $senderName,
                'message' => $messagePreview,
            ]
        );

        return $this->createNotification(
            $user,
            NotificationType::MESSAGE,
            $title,
            $content,
            $data
        );
    }

    /**
     * 创建系统通知
     *
     * @param User|int $user 用户
     * @param string $title 标题
     * @param string $content 内容
     * @param array $data 额外数据
     * @return Notification
     */
    public function notifySystem($user, string $title, string $content, array $data = []): Notification
    {
        return $this->createNotification(
            $user,
            NotificationType::SYSTEM,
            $title,
            $content,
            $data
        );
    }

    /**
     * 批量创建通知
     *
     * @param array $userIds 用户ID数组
     * @param string $type 通知类型
     * @param string $title 通知标题
     * @param string $content 通知内容
     * @param array $data 额外数据
     * @return int 创建的通知数量
     */
    public function createBulkNotifications(array $userIds, string $type, string $title, string $content, array $data = []): int
    {
        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'content' => $content,
                'data' => json_encode($data),
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Notification::insert($notifications);

        return count($notifications);
    }

    /**
     * 创建公告通知（给所有用户）
     *
     * @param string $title 标题
     * @param string $content 内容
     * @param array $data 额外数据
     * @return int 创建的通知数量
     */
    public function createAnnouncementForAll(string $title, string $content, array $data = []): int
    {
        $userIds = User::where('status', User::STATUS_ACTIVE)->pluck('id')->toArray();

        return $this->createBulkNotifications(
            $userIds,
            NotificationType::ANNOUNCEMENT,
            $title,
            $content,
            $data
        );
    }

    /**
     * 为通知附加翻译元数据，供前端多语言渲染。
     */
    protected function injectTranslationMeta(string $type, array $data, array $params = []): array
    {
        $keys = NotificationType::translationKeys($type);

        if (!empty($keys['title']) || !empty($keys['body'])) {
            $data['translation'] = [
                'title_key' => $keys['title'],
                'body_key' => $keys['body'],
                'params' => $params,
            ];
        }

        return $data;
    }

    /**
     * 将通知数据转换为字符串，确保兼容 FCM payload 要求
     */
    protected function preparePushData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($key === 'translation') {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $normalized[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $normalized[$key] = $value ? '1' : '0';
            } elseif ($value === null) {
                $normalized[$key] = '';
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}


