package services

import (
	"context"
	"fmt"
	"kelisim-chat/internal/config"
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/models"
	"time"

	firebase "firebase.google.com/go/v4"
	"firebase.google.com/go/v4/messaging"
	"github.com/sirupsen/logrus"
	"google.golang.org/api/option"
)

// FCMService Firebase Cloud Messaging V1 服务
type FCMService struct {
	client *messaging.Client
}

var fcmService *FCMService

// InitFCMService 初始化 FCM 服务
func InitFCMService() error {
	serviceAccountPath := config.AppConfig.FCMServiceAccountPath
	if serviceAccountPath == "" {
		logrus.Warn("FCM service account path not configured, push notifications disabled")
		return nil
	}

	ctx := context.Background()
	opt := option.WithCredentialsFile(serviceAccountPath)
	
	app, err := firebase.NewApp(ctx, nil, opt)
	if err != nil {
		return fmt.Errorf("error initializing Firebase app: %v", err)
	}

	client, err := app.Messaging(ctx)
	if err != nil {
		return fmt.Errorf("error getting Messaging client: %v", err)
	}

	fcmService = &FCMService{
		client: client,
	}

	logrus.Info("FCM Service initialized successfully")
	return nil
}

// GetFCMService 获取 FCM 服务实例
func GetFCMService() *FCMService {
	return fcmService
}

// SendNotification 发送推送通知
func (s *FCMService) SendNotification(token, title, body string, data map[string]string) error {
	if s == nil || s.client == nil {
		logrus.Warn("FCM service not initialized")
		return nil
	}

	message := &messaging.Message{
		Token: token,
		Notification: &messaging.Notification{
			Title: title,
			Body:  body,
		},
		Data: data,
		Android: &messaging.AndroidConfig{
			Priority: "high",
			Notification: &messaging.AndroidNotification{
				Sound: "default",
			},
		},
		APNS: &messaging.APNSConfig{
			Payload: &messaging.APNSPayload{
				Aps: &messaging.Aps{
					Sound: "default",
				},
			},
		},
	}

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	response, err := s.client.Send(ctx, message)
	if err != nil {
		return fmt.Errorf("failed to send FCM message: %v", err)
	}

	logrus.Infof("Successfully sent message: %s", response)
	return nil
}

// SendNotificationToUser 发送通知给指定用户的所有设备
func (s *FCMService) SendNotificationToUser(userID uint, title, body string, data map[string]string) error {
	if s == nil || s.client == nil {
		logrus.Warn("FCM service not initialized, skipping notification")
		return nil
	}

	// 获取用户的所有设备 Token
	var tokens []models.DeviceToken
	err := database.DB.Where("user_id = ? AND is_active = ?", userID, true).Find(&tokens).Error
	if err != nil {
		return err
	}

	if len(tokens) == 0 {
		logrus.Infof("User %d has no active device tokens", userID)
		return nil
	}

	// 发送推送通知到所有设备
	for _, deviceToken := range tokens {
		go func(token models.DeviceToken) {
			err := s.SendNotification(token.Token, title, body, data)
			if err != nil {
				logrus.Errorf("Failed to send notification to device %s: %v", token.Token[:10], err)
				
				// 如果是无效 token 错误，标记为不活跃
				if isInvalidTokenError(err) {
					database.DB.Model(&models.DeviceToken{}).
						Where("user_id = ? AND token = ?", token.UserID, token.Token).
						Update("is_active", false)
				}
			}
		}(deviceToken)
	}

	return nil
}

// SendChatMessageNotification 发送聊天消息通知
func (s *FCMService) SendChatMessageNotification(message *models.Message, recipientUserIDs []uint) error {
	if s == nil || s.client == nil {
		logrus.Warn("FCM service not initialized, skipping notification")
		return nil
	}

	if message.Sender == nil {
		return fmt.Errorf("message sender is nil")
	}

	senderName := message.Sender.GetFullName()
	if senderName == "" {
		if message.Sender.Email != nil {
			senderName = *message.Sender.Email
		} else {
			senderName = "未知用户"
		}
	}

	title := fmt.Sprintf("来自 %s 的新消息", senderName)
	bodyText := ""

	// 根据消息类型设置内容
	switch message.Type {
	case "text":
		if message.Content != nil {
			bodyText = *message.Content
		}
	case "image":
		bodyText = "[图片]"
	case "document":
		bodyText = "[文档]"
	default:
		bodyText = "[消息]"
	}

	// 构建数据
	data := map[string]string{
		"type":       "chat_message",
		"chat_id":    fmt.Sprintf("%d", message.ChatID),
		"message_id": fmt.Sprintf("%d", message.ID),
		"sender_id":  fmt.Sprintf("%d", *message.SenderID),
	}

	// 发送给所有接收者
	for _, userID := range recipientUserIDs {
		// 不发送给发送者自己
		if message.SenderID != nil && userID == *message.SenderID {
			continue
		}

		go func(uid uint) {
			err := s.SendNotificationToUser(uid, title, bodyText, data)
			if err != nil {
				logrus.Errorf("Failed to send notification to user %d: %v", uid, err)
			}
		}(userID)
	}

	return nil
}

// isInvalidTokenError 检查是否是无效 Token 错误
func isInvalidTokenError(err error) bool {
	if err == nil {
		return false
	}
	errStr := err.Error()
	return contains(errStr, "registration-token-not-registered") ||
		contains(errStr, "invalid-registration-token") ||
		contains(errStr, "invalid-argument")
}

func contains(s, substr string) bool {
	return len(s) >= len(substr) && (s == substr || len(s) > len(substr) && 
		(s[:len(substr)] == substr || s[len(s)-len(substr):] == substr || 
		findSubstring(s, substr)))
}

func findSubstring(s, substr string) bool {
	for i := 0; i <= len(s)-len(substr); i++ {
		if s[i:i+len(substr)] == substr {
			return true
		}
	}
	return false
}

