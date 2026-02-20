package services

import (
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/models"
	"time"

	"gorm.io/gorm"
)

// MessageService 消息服务
type MessageService struct{}

// NewMessageService 创建消息服务
func NewMessageService() *MessageService {
	return &MessageService{}
}

// SendMessage 发送消息
func (s *MessageService) SendMessage(chatID uint, senderID *uint, messageType string, content *string, fileURL *string, fileName *string, fileSize *int64) (*models.Message, error) {
	// 开始事务
	tx := database.DB.Begin()
	defer func() {
		if r := recover(); r != nil {
			tx.Rollback()
		}
	}()

	// 创建消息
	message := &models.Message{
		ChatID:    chatID,
		SenderID:  senderID,
		Type:      messageType,
		Content:   content,
		FileURL:   fileURL,
		FileName:  fileName,
		FileSize:  fileSize,
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}

	if err := tx.Create(message).Error; err != nil {
		tx.Rollback()
		return nil, err
	}

	// 如果是文件消息，创建文件记录
	if messageType == "document" || messageType == "image" {
		fileType := "document"
		if messageType == "image" {
			fileType = "image"
		}

		chatFile := &models.ChatFile{
			ChatID:     chatID,
			MessageID:  message.ID,
			FileType:   fileType,
			FileURL:    *fileURL,
			FileName:   *fileName,
			FileSize:   *fileSize,
			UploadedBy: *senderID,
			CreatedAt:  time.Now(),
		}

		if err := tx.Create(chatFile).Error; err != nil {
			tx.Rollback()
			return nil, err
		}
	}

	// 更新聊天室的更新时间
	if err := tx.Model(&models.Chat{}).Where("id = ?", chatID).Update("updated_at", time.Now()).Error; err != nil {
		tx.Rollback()
		return nil, err
	}

	// 提交事务
	if err := tx.Commit().Error; err != nil {
		return nil, err
	}

	// 预加载关联数据
	if err := database.DB.Preload("Sender").First(message, message.ID).Error; err != nil {
		return nil, err
	}

	return message, nil
}

// GetChatMessages 获取聊天室消息
func (s *MessageService) GetChatMessages(chatID uint, limit int, offset int) ([]models.Message, error) {
	var messages []models.Message

	query := database.DB.Where("chat_id = ? AND deleted_at IS NULL", chatID).
		Preload("Sender").
		Order("created_at ASC").
		Limit(limit)

	if offset > 0 {
		query = query.Offset(offset)
	}

	err := query.Find(&messages).Error
	return messages, err
}

// GetMessageByID 根据ID获取消息
func (s *MessageService) GetMessageByID(messageID uint) (*models.Message, error) {
	var message models.Message

	err := database.DB.Where("id = ? AND deleted_at IS NULL", messageID).
		Preload("Sender").
		First(&message).Error

	return &message, err
}

// MarkAsRead 标记消息为已读
func (s *MessageService) MarkAsRead(messageID uint, userID uint) error {
	// 检查消息状态是否已存在
	var status models.MessageStatus
	err := database.DB.Where("message_id = ? AND user_id = ?", messageID, userID).First(&status).Error

	if err == gorm.ErrRecordNotFound {
		// 创建新的状态记录
		status = models.MessageStatus{
			MessageID: messageID,
			UserID:    userID,
			Status:    "read",
			UpdatedAt: time.Now(),
		}
		return database.DB.Create(&status).Error
	} else if err != nil {
		return err
	}

	// 更新现有状态
	return database.DB.Model(&status).Update("status", "read").Error
}

// MarkChatAsRead 标记整个聊天室为已读
func (s *MessageService) MarkChatAsRead(chatID uint, userID uint) error {
	// 获取聊天室中所有未读消息
	var messageIDs []uint
	err := database.DB.Model(&models.Message{}).
		Where("chat_id = ? AND deleted_at IS NULL", chatID).
		Pluck("id", &messageIDs).Error

	if err != nil {
		return err
	}

	if len(messageIDs) == 0 {
		return nil
	}

	// 批量创建或更新消息状态
	for _, messageID := range messageIDs {
		var status models.MessageStatus
		err := database.DB.Where("message_id = ? AND user_id = ?", messageID, userID).First(&status).Error

		if err == gorm.ErrRecordNotFound {
			// 创建新的状态记录
			status = models.MessageStatus{
				MessageID: messageID,
				UserID:    userID,
				Status:    "read",
				UpdatedAt: time.Now(),
			}
			if err := database.DB.Create(&status).Error; err != nil {
				return err
			}
		} else if err != nil {
			return err
		} else {
			// 更新现有状态
			if err := database.DB.Model(&status).Update("status", "read").Error; err != nil {
				return err
			}
		}
	}

	return nil
}

// DeleteMessage 删除消息（软删除）
func (s *MessageService) DeleteMessage(messageID uint, userID uint) error {
	// 检查消息是否属于该用户
	var message models.Message
	err := database.DB.Where("id = ? AND sender_id = ?", messageID, userID).First(&message).Error
	if err != nil {
		return err
	}

	// 软删除消息
	return database.DB.Model(&message).Update("deleted_at", time.Now()).Error
}

// GetUnreadCount 获取用户未读消息数量
func (s *MessageService) GetUnreadCount(userID uint) (int64, error) {
	var count int64

	// 获取用户参与的所有聊天室
	var chatIDs []uint
	err := database.DB.Model(&models.ChatParticipant{}).
		Where("user_id = ?", userID).
		Pluck("chat_id", &chatIDs).Error

	if err != nil {
		return 0, err
	}

	if len(chatIDs) == 0 {
		return 0, nil
	}

	// 计算未读消息数量
	err = database.DB.Model(&models.Message{}).
		Where("chat_id IN ? AND deleted_at IS NULL", chatIDs).
		Where("id NOT IN (SELECT message_id FROM message_status WHERE user_id = ? AND status = 'read')", userID).
		Count(&count).Error

	return count, err
}
