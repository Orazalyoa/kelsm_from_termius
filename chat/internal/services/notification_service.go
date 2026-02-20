package services

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"kelisim-chat/internal/config"
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/models"
	"net/http"
	"time"

	"github.com/sirupsen/logrus"
)

// NotificationService 推送通知服务
type NotificationService struct{}

// NewNotificationService 创建推送通知服务
func NewNotificationService() *NotificationService {
	return &NotificationService{}
}

// FCMLegacyMessage Firebase Cloud Messaging Legacy API 消息格式
type FCMLegacyMessage struct {
	To               string                 `json:"to"`
	Notification     FCMNotification        `json:"notification"`
	Data             map[string]interface{} `json:"data,omitempty"`
	Priority         string                 `json:"priority,omitempty"`
	ContentAvailable bool                   `json:"content_available,omitempty"`
}

type FCMNotification struct {
	Title string `json:"title"`
	Body  string `json:"body"`
}

// RegisterDeviceToken 注册设备 Token
func (s *NotificationService) RegisterDeviceToken(userID uint, token, platform, deviceID string) error {
	// 检查是否已存在
	var existingToken models.DeviceToken
	err := database.DB.Where("user_id = ? AND token = ?", userID, token).First(&existingToken).Error

	if err == nil {
		// 已存在，更新时间和状态
		return database.DB.Model(&existingToken).Updates(map[string]interface{}{
			"is_active":  true,
			"platform":   platform,
			"device_id":  deviceID,
			"updated_at": time.Now(),
		}).Error
	}

	// 不存在，创建新记录
	deviceToken := &models.DeviceToken{
		UserID:    userID,
		Token:     token,
		Platform:  platform,
		DeviceID:  deviceID,
		IsActive:  true,
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}

	return database.DB.Create(deviceToken).Error
}

// UnregisterDeviceToken 注销设备 Token
func (s *NotificationService) UnregisterDeviceToken(userID uint, token string) error {
	return database.DB.Model(&models.DeviceToken{}).
		Where("user_id = ? AND token = ?", userID, token).
		Update("is_active", false).Error
}

// GetUserDeviceTokens 获取用户的所有活跃设备 Token
func (s *NotificationService) GetUserDeviceTokens(userID uint) ([]models.DeviceToken, error) {
	var tokens []models.DeviceToken
	err := database.DB.Where("user_id = ? AND is_active = ?", userID, true).Find(&tokens).Error
	return tokens, err
}

// SendNotificationToUser 发送通知给指定用户
func (s *NotificationService) SendNotificationToUser(userID uint, title, body string, data map[string]interface{}) error {
	// 获取用户的所有设备 Token
	tokens, err := s.GetUserDeviceTokens(userID)
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
			err := s.sendFCMNotification(token.Token, title, body, data, token.Platform)
			if err != nil {
				logrus.Errorf("Failed to send notification to device %s: %v", token.Token, err)
				// 如果是 token 无效，标记为不活跃
				if isInvalidTokenError(err) {
					s.UnregisterDeviceToken(token.UserID, token.Token)
				}
			}
		}(deviceToken)
	}

	return nil
}

// sendFCMNotification 通过 FCM Legacy API 发送推送通知
func (s *NotificationService) sendFCMNotification(token, title, body string, data map[string]interface{}, platform string) error {
	fcmServerKey := config.AppConfig.FCMServerKey
	if fcmServerKey == "" {
		logrus.Warn("FCM server key not configured")
		return nil
	}

	// 确保 data 中的值都是字符串（FCM Legacy API 要求）
	stringData := make(map[string]string)
	for k, v := range data {
		stringData[k] = fmt.Sprintf("%v", v)
	}

	// 构建 FCM Legacy 消息
	message := FCMLegacyMessage{
		To: token,
		Notification: FCMNotification{
			Title: title,
			Body:  body,
		},
		Data:             make(map[string]interface{}),
		Priority:         "high",
		ContentAvailable: true,
	}

	// 将字符串数据添加到消息中
	for k, v := range stringData {
		message.Data[k] = v
	}

	// 发送 HTTP 请求到 FCM
	jsonData, err := json.Marshal(message)
	if err != nil {
		return err
	}

	// 使用 Legacy FCM API (更简单，无需 OAuth)
	// 生产环境建议使用 FCM HTTP v1 API
	fcmURL := "https://fcm.googleapis.com/fcm/send"
	req, err := http.NewRequest("POST", fcmURL, bytes.NewBuffer(jsonData))
	if err != nil {
		return err
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", fmt.Sprintf("key=%s", fcmServerKey))

	client := &http.Client{Timeout: 10 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("FCM returned status %d: %s", resp.StatusCode, string(body))
	}

	logrus.Infof("Push notification sent successfully to token: %s", token[:10]+"...")
	return nil
}

// SendChatMessageNotification 发送聊天消息通知
func (s *NotificationService) SendChatMessageNotification(message *models.Message, recipientUserIDs []uint) error {
	if message.Sender == nil {
		return fmt.Errorf("message sender is nil")
	}

	senderName := message.Sender.GetFullName()
	if senderName == "" {
		// Email 是指针类型，需要检查并解引用
		if message.Sender.Email != nil {
			senderName = *message.Sender.Email
		} else {
			senderName = "未知用户"
		}
	}

	title := fmt.Sprintf("来自 %s 的新消息", senderName)
	body := ""

	// 根据消息类型设置内容
	switch message.Type {
	case "text":
		if message.Content != nil {
			body = *message.Content
		}
	case "image":
		body = "[图片]"
	case "document":
		body = "[文档]"
	default:
		body = "[消息]"
	}

	// 构建数据
	data := map[string]interface{}{
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
			err := s.SendNotificationToUser(uid, title, body, data)
			if err != nil {
				logrus.Errorf("Failed to send notification to user %d: %v", uid, err)
			}
		}(userID)
	}

	return nil
}
