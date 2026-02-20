# Push Notifications Guide - Go Chat Backend

## Overview

The Go chat backend has Firebase Cloud Messaging (FCM) integration for sending push notifications to mobile devices when new messages arrive.

## Features

✅ **Already Implemented:**
- Device token registration and management
- Push notification sending via FCM Legacy API
- Automatic notification on new chat messages
- Multi-device support per user
- Automatic token cleanup for invalid/expired devices
- Asynchronous notification sending (non-blocking)

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

### 2. How It Works

#### Device Registration

When a user opens the mobile app:
1. App requests notification permissions
2. App registers with FCM and gets a device token
3. App sends token to backend via `POST /notifications/register`
4. Backend stores token in `device_tokens` table

#### Sending Notifications

When a new message is sent:
1. Message is saved to database
2. Message is sent via WebSocket to online users
3. **Push notification is sent to offline users** (line 134 in `message.go`)
4. Notification includes:
   - Sender name
   - Message preview
   - Chat ID for deep linking

#### Notification Handler

```go
// internal/handlers/message.go (line 134)
go h.notificationService.SendChatMessageNotification(message, recipientUserIDs)
```

#### Notification Service

The `NotificationService` in `internal/services/notification_service.go` handles:
- Fetching user device tokens
- Formatting notification payload
- Sending to FCM API
- Error handling and retry logic
- Invalid token cleanup

## API Endpoints

### Register Device Token
```http
POST /notifications/register
Authorization: Bearer {jwt-token}
Content-Type: application/json

{
  "token": "fcm-device-token",
  "platform": "android",
  "device_id": "unique-device-id"
}
```

### Unregister Device Token
```http
POST /notifications/unregister
Authorization: Bearer {jwt-token}
Content-Type: application/json

{
  "token": "fcm-device-token"
}
```

### Get Device Tokens
```http
GET /notifications/devices
Authorization: Bearer {jwt-token}
```

## Database Schema

### device_tokens Table

```sql
CREATE TABLE device_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    platform ENUM('ios', 'android', 'web') NOT NULL,
    device_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    UNIQUE KEY unique_user_token (user_id, token)
);
```

## Notification Format

### FCM Payload Structure

```json
{
  "to": "device-token",
  "notification": {
    "title": "来自 John Doe 的新消息",
    "body": "Hello! How are you?"
  },
  "data": {
    "type": "chat_message",
    "chat_id": "123",
    "message_id": "456",
    "sender_id": "789"
  },
  "priority": "high",
  "content_available": true
}
```

## Testing

### 1. Test Device Registration

```bash
curl -X POST http://localhost:8080/notifications/register \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "test-fcm-token",
    "platform": "android",
    "device_id": "test-device-123"
  }'
```

### 2. Send a Test Message

Send a message through the chat API and check if push notification is received on the device.

### 3. Check Logs

The service logs all notification sending attempts:

```
[INFO] Push notification sent successfully to token: eQU8Xzh...
[ERROR] Failed to send notification to device: invalid-registration-token
```

## Troubleshooting

### Notifications not being sent?

1. **Check FCM_SERVER_KEY is set**
   ```bash
   echo $FCM_SERVER_KEY
   ```

2. **Verify device token is registered**
   ```sql
   SELECT * FROM device_tokens WHERE user_id = ? AND is_active = 1;
   ```

3. **Check application logs**
   ```bash
   tail -f logs/app.log | grep Push
   ```

4. **Test FCM API directly**
   ```bash
   curl -X POST https://fcm.googleapis.com/fcm/send \
     -H "Authorization: key=YOUR_FCM_SERVER_KEY" \
     -H "Content-Type: application/json" \
     -d '{
       "to": "device-token",
       "notification": {
         "title": "Test",
         "body": "Test message"
       }
     }'
   ```

### Common Error Codes

- **InvalidRegistration**: Token format is invalid
- **NotRegistered**: Token has been unregistered or expired
- **401 Unauthorized**: FCM Server Key is invalid

## Production Recommendations

### 1. Use FCM HTTP v1 API

The current implementation uses the Legacy API. For production, consider migrating to the HTTP v1 API which requires:
- Firebase Admin SDK
- Service account JSON file
- OAuth 2.0 tokens

### 2. Add Rate Limiting

Implement rate limiting for notification endpoints to prevent abuse.

### 3. Add Notification Preferences

Allow users to configure notification preferences:
- Enable/disable notifications
- Quiet hours
- Notification sound/vibration

### 4. Monitor Metrics

Track:
- Notification send success rate
- Failed delivery count
- Average delivery time
- Token refresh rate

### 5. Batch Notifications

For group chats with many participants, consider batching notifications using FCM's multicast feature.

## Security Notes

⚠️ **Important:**
- Never expose FCM_SERVER_KEY in client code
- Store server key securely using environment variables or secret management
- Validate device tokens before storing
- Implement HTTPS only for production
- Rate limit notification registration endpoints

## Related Files

- `internal/services/notification_service.go` - Main notification logic
- `internal/handlers/notification.go` - API endpoints
- `internal/handlers/message.go` - Message handler with notification trigger
- `internal/models/device_token.go` - Device token model
- `internal/config/config.go` - Configuration loading

