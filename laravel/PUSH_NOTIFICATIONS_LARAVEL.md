# Push Notifications Setup - Laravel Backend

## Overview

The Laravel backend now supports Firebase Cloud Messaging (FCM) for sending push notifications for system events, consultations, and announcements.

## Configuration

### 1. Environment Variables

Add the following to your `.env` file:

```env
# Firebase Cloud Messaging
FCM_SERVER_KEY=your-firebase-server-key-here
```

Get your FCM Server Key from:
1. Firebase Console → Project Settings
2. Cloud Messaging tab
3. Copy the "Server key" (Legacy format)

**Note:** This should be the same FCM_SERVER_KEY as used in the Go chat backend.

### 2. Database Connection

The NotificationService queries the `device_tokens` table from the chat service database. Ensure your `config/database.php` has the correct MySQL connection configured.

## Implementation

### How It Works

1. **Notification Created**: When any notification is created via `NotificationService::createNotification()`
2. **Database Saved**: Notification is saved to the `notifications` table
3. **Push Notification Sent**: System automatically sends push notification to user's registered devices
4. **FCM Delivery**: Firebase delivers notification to Android/iOS devices

### Automatic Push Notifications

All notification methods automatically trigger push notifications:

```php
// Consultation status change
$notificationService->notifyConsultationStatusChange($user, $consultationId, 'pending', 'in_progress');
// → Creates DB notification + Sends push notification

// Consultation completed
$notificationService->notifyConsultationCompleted($client, $consultationId, 3);
// → Creates DB notification + Sends push notification

// System announcement
$notificationService->notifySystem($user, 'System Update', 'We have updated our terms');
// → Creates DB notification + Sends push notification
```

### Code Structure

#### NotificationService.php

**Updated Methods:**
- `createNotification()` - Now calls `sendPushNotification()` after creating DB record
- `sendPushNotification()` - Fetches device tokens and sends to FCM
- `getUserDeviceTokens()` - Retrieves active device tokens from database
- `sendFCMNotification()` - Makes HTTP request to FCM API

#### Push Notification Flow

```
NotificationService::createNotification()
    ↓
    Creates notification in DB
    ↓
    Calls sendPushNotification()
    ↓
    Fetches user device tokens
    ↓
    For each device token:
        → sendFCMNotification()
        → HTTP POST to FCM API
        → FCM delivers to device
```

## Notification Types

### 1. Consultation Status Change
```php
$notificationService->notifyConsultationStatusChange(
    $user,
    $consultationId,
    'pending',
    'in_progress'
);
```

Push notification includes:
- Type: `consultation_status`
- Title: "咨询状态变更"
- Body: Status change message
- Data: `consultation_id`, `old_status`, `new_status`

### 2. Consultation Completed
```php
$notificationService->notifyConsultationCompleted(
    $client,
    $consultationId,
    $deliverablesCount
);
```

Push notification includes:
- Type: `consultation_completed`
- Title: "咨询已完成"
- Body: Completion message with deliverables count
- Data: `consultation_id`, `deliverables_count`

### 3. Consultation Assigned (to Lawyer)
```php
$notificationService->notifyConsultationAssigned(
    $lawyer,
    $consultationId,
    $consultationTitle
);
```

### 4. Priority Escalated
```php
$notificationService->notifyPriorityEscalated(
    $lawyer,
    $consultationId,
    'medium',
    'high'
);
```

### 5. System Notification
```php
$notificationService->notifySystem(
    $user,
    'System Maintenance',
    'System will be down for maintenance tonight'
);
```

### 6. Bulk Notifications
```php
$notificationService->createBulkNotifications(
    [1, 2, 3, 4, 5], // User IDs
    'announcement',
    'New Feature Released',
    'Check out our new chat feature'
);
```

**Note:** Push notifications are sent asynchronously for each user in bulk operations.

## Testing

### 1. Test Single Notification

```php
use App\Services\NotificationService;
use App\Models\User;

$notificationService = new NotificationService();
$user = User::find(1);

$notificationService->notifySystem(
    $user,
    'Test Notification',
    'This is a test push notification'
);
```

### 2. Check Logs

Laravel logs all push notification attempts:

```bash
tail -f storage/logs/laravel.log | grep "Push notification"
```

Success log:
```
[INFO] Push notification sent successfully to token: eQU8Xzh...
```

Error log:
```
[ERROR] Failed to send push notification: Connection timeout
```

### 3. Test via Artisan Console

```php
php artisan tinker

>>> $service = new \App\Services\NotificationService();
>>> $service->notifySystem(1, 'Test', 'Test message', ['test' => 'true']);
```

## Database Schema

The service queries the `device_tokens` table:

```sql
SELECT token FROM device_tokens 
WHERE user_id = ? AND is_active = 1;
```

## Error Handling

### Common Issues

1. **FCM_SERVER_KEY not configured**
   - Warning logged: "FCM_SERVER_KEY not configured, skipping push notification"
   - Notification still saved to database
   - No push sent

2. **User has no device tokens**
   - Info logged: "User {id} has no active device tokens"
   - Notification saved to database
   - No push sent (expected behavior)

3. **FCM API error**
   - Error logged with response body
   - Notification saved to database
   - Push failed but notification still accessible in app

4. **Database connection error**
   - Error logged: "Failed to fetch device tokens"
   - Falls back gracefully
   - Notification still saved

## Production Recommendations

### 1. Use Queue for Push Notifications

Currently push notifications are sent synchronously. For better performance:

```php
// In NotificationService::createNotification()
dispatch(new SendPushNotificationJob($userId, $title, $content, $data));
```

### 2. Add Retry Logic

Implement retry logic for failed FCM requests:

```php
Http::retry(3, 100)->post(/* ... */);
```

### 3. Monitor Failed Notifications

Track failed notification deliveries:

```php
if (!$response->successful()) {
    // Log to monitoring service (Sentry, etc.)
    // Store failed attempt for retry
}
```

### 4. Respect User Preferences

Before sending, check user notification preferences:

```php
$settings = UserSettings::where('user_id', $userId)->first();
if (!$settings->notifications_enabled) {
    return; // Skip notification
}
```

### 5. Rate Limiting

Implement rate limiting to prevent notification spam:

```php
if ($this->hasSentRecentNotification($userId, $type)) {
    return; // Skip duplicate notification
}
```

## Security Notes

⚠️ **Important:**
- Never expose FCM_SERVER_KEY in client code or version control
- Add FCM_SERVER_KEY to `.env` file (not committed)
- Use separate FCM projects for development/staging/production
- Validate notification data before sending
- Sanitize user input in notification content

## Integration Points

### Where Notifications Are Sent

1. **Consultation Status Changes**
   - Location: `ConsultationController` or `ConsultationService`
   - When: Status updated via admin panel or API

2. **Consultation Completion**
   - Location: `ConsultationService::completeConsultation()`
   - When: Lawyer marks consultation as completed

3. **System Announcements**
   - Location: `AnnouncementController::store()`
   - When: Admin creates new announcement

4. **User Actions**
   - Location: Various controllers
   - When: Important user actions require notification

## Testing Checklist

- [ ] FCM_SERVER_KEY configured in .env
- [ ] Laravel can connect to MySQL database
- [ ] device_tokens table accessible
- [ ] Test notification sent successfully
- [ ] Notification appears in mobile app
- [ ] Notification tap navigates correctly
- [ ] Logs show successful delivery
- [ ] Error handling works (invalid token, etc.)
- [ ] Bulk notifications work
- [ ] Performance is acceptable

## Related Files

- `app/Services/NotificationService.php` - Main notification logic
- `app/Models/Notification.php` - Notification model
- `config/database.php` - Database configuration
- `.env` - Environment configuration

## Troubleshooting

### Notifications not being sent?

1. Check FCM_SERVER_KEY in `.env`:
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

2. Verify database connection:
   ```bash
   php artisan tinker
   >>> DB::table('device_tokens')->count()
   ```

3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. Test FCM directly:
   ```bash
   curl -X POST https://fcm.googleapis.com/fcm/send \
     -H "Authorization: key=YOUR_KEY" \
     -H "Content-Type: application/json" \
     -d '{"to":"device-token","notification":{"title":"Test","body":"Test"}}'
   ```

### Invalid registration token errors?

Device tokens can become invalid if:
- App is uninstalled
- User logs out
- Token expires

The Go backend handles token cleanup automatically when sending chat notifications.

