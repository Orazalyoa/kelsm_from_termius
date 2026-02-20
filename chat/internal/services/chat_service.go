package services

import (
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/models"
	"time"

	"gorm.io/gorm"
)

// ChatService 聊天服务
type ChatService struct{}

// NewChatService 创建聊天服务
func NewChatService() *ChatService {
	return &ChatService{}
}

// CreateChat 创建聊天室
func (s *ChatService) CreateChat(title string, chatType string, createdBy uint, participantIDs []uint) (*models.Chat, error) {
	// 开始事务
	tx := database.DB.Begin()
	defer func() {
		if r := recover(); r != nil {
			tx.Rollback()
		}
	}()

	// 创建聊天室
	chat := &models.Chat{
		Title:     title,
		Type:      chatType,
		CreatedBy: createdBy,
	}

	if err := tx.Create(chat).Error; err != nil {
		tx.Rollback()
		return nil, err
	}

	// 添加参与者
	participants := make([]models.ChatParticipant, 0, len(participantIDs)+1)

	// 添加创建者
	participants = append(participants, models.ChatParticipant{
		ChatID:   chat.ID,
		UserID:   createdBy,
		JoinedAt: time.Now(),
	})

	// 添加其他参与者
	for _, userID := range participantIDs {
		if userID != createdBy { // 避免重复添加创建者
			participants = append(participants, models.ChatParticipant{
				ChatID:   chat.ID,
				UserID:   userID,
				JoinedAt: time.Now(),
			})
		}
	}

	if err := tx.Create(&participants).Error; err != nil {
		tx.Rollback()
		return nil, err
	}

	// 提交事务
	if err := tx.Commit().Error; err != nil {
		return nil, err
	}

	// 预加载关联数据
	if err := database.DB.Preload("Creator").Preload("Participants.User").First(chat, chat.ID).Error; err != nil {
		return nil, err
	}

	return chat, nil
}

// GetUserChats 获取用户的聊天列表
func (s *ChatService) GetUserChats(userID uint) ([]models.Chat, error) {
	var chats []models.Chat

	err := database.DB.
		Joins("JOIN chat_participants ON chats.id = chat_participants.chat_id").
		Where("chat_participants.user_id = ? AND chats.deleted_at IS NULL", userID).
		Preload("Creator").
		Preload("Participants", func(db *gorm.DB) *gorm.DB {
			return db.Order("chat_participants.role DESC, chat_participants.created_at ASC")
		}).
		Preload("Participants.User").
		Distinct().
		Order("chats.updated_at DESC").
		Find(&chats).Error

	return chats, err
}

// GetAllChats 获取所有聊天列表 (Operator 专用)
func (s *ChatService) GetAllChats() ([]models.Chat, error) {
	var chats []models.Chat

	err := database.DB.
		Where("deleted_at IS NULL").
		Preload("Creator").
		Preload("Participants.User").
		Order("updated_at DESC").
		Find(&chats).Error

	return chats, err
}

// GetChatByID 根据ID获取聊天室
func (s *ChatService) GetChatByID(chatID uint, userID uint) (*models.Chat, error) {
	var chat models.Chat

	err := database.DB.
		Joins("JOIN chat_participants ON chats.id = chat_participants.chat_id").
		Where("chats.id = ? AND chat_participants.user_id = ? AND chats.deleted_at IS NULL", chatID, userID).
		Preload("Creator").
		Preload("Participants.User").
		First(&chat).Error

	return &chat, err
}

// GetChatByIDForOperator 根据ID获取聊天室 (Operator 专用，无权限限制)
func (s *ChatService) GetChatByIDForOperator(chatID uint) (*models.Chat, error) {
	var chat models.Chat

	err := database.DB.
		Where("id = ? AND deleted_at IS NULL", chatID).
		Preload("Creator").
		Preload("Participants.User").
		First(&chat).Error

	return &chat, err
}

// AddParticipant 添加参与者
func (s *ChatService) AddParticipant(chatID uint, userID uint, role string) error {
	// 检查用户是否已经是参与者
	var count int64
	if err := database.DB.Model(&models.ChatParticipant{}).
		Where("chat_id = ? AND user_id = ?", chatID, userID).
		Count(&count).Error; err != nil {
		return err
	}

	if count > 0 {
		return gorm.ErrDuplicatedKey
	}

	// 添加参与者
	participant := &models.ChatParticipant{
		ChatID:   chatID,
		UserID:   userID,
		Role:     &role,
		JoinedAt: time.Now(),
	}

	return database.DB.Create(participant).Error
}

// RemoveParticipant 移除参与者
func (s *ChatService) RemoveParticipant(chatID uint, userID uint) error {
	return database.DB.Where("chat_id = ? AND user_id = ?", chatID, userID).
		Delete(&models.ChatParticipant{}).Error
}

// GetChatParticipants 获取聊天室参与者
func (s *ChatService) GetChatParticipants(chatID uint) ([]models.ChatParticipant, error) {
	var participants []models.ChatParticipant

	err := database.DB.Where("chat_id = ?", chatID).
		Preload("User").
		Find(&participants).Error

	return participants, err
}

// IsUserInChat 检查用户是否在聊天室中
func (s *ChatService) IsUserInChat(chatID uint, userID uint) bool {
	var count int64
	database.DB.Model(&models.ChatParticipant{}).
		Where("chat_id = ? AND user_id = ?", chatID, userID).
		Count(&count)
	return count > 0
}

// UpdateLastReadAt 更新用户最后读取时间
func (s *ChatService) UpdateLastReadAt(chatID uint, userID uint) error {
	return database.DB.Model(&models.ChatParticipant{}).
		Where("chat_id = ? AND user_id = ?", chatID, userID).
		Update("last_read_at", time.Now()).Error
}

// IsCompanyAdmin 检查用户是否为公司管理员
func (s *ChatService) IsCompanyAdmin(userID uint) (bool, error) {
	var user models.User
	err := database.DB.Select("user_type").First(&user, userID).Error
	if err != nil {
		return false, err
	}
	return user.IsCompanyAdmin(), nil
}

// AreUsersInSameOrganization 检查两个用户是否在同一组织
func (s *ChatService) AreUsersInSameOrganization(userID1 uint, userID2 uint) (bool, error) {
	// 查询用户1的组织ID列表
	var org1IDs []uint
	err := database.DB.Table("organization_members").
		Select("organization_id").
		Where("user_id = ?", userID1).
		Pluck("organization_id", &org1IDs).Error
	if err != nil {
		return false, err
	}

	if len(org1IDs) == 0 {
		return false, nil
	}

	// 检查用户2是否在相同的组织中
	var count int64
	err = database.DB.Table("organization_members").
		Where("user_id = ? AND organization_id IN ?", userID2, org1IDs).
		Count(&count).Error
	if err != nil {
		return false, err
	}

	return count > 0, nil
}

// GetOrganizationMembers 获取用户所在组织的所有成员
func (s *ChatService) GetOrganizationMembers(userID uint) ([]models.User, error) {
	var users []models.User

	// 首先获取用户所在的组织ID列表
	var orgIDs []uint
	err := database.DB.Table("organization_members").
		Select("organization_id").
		Where("user_id = ?", userID).
		Pluck("organization_id", &orgIDs).Error
	if err != nil {
		return nil, err
	}

	if len(orgIDs) == 0 {
		return []models.User{}, nil
	}

	// 获取这些组织的所有成员（排除当前用户）
	err = database.DB.
		Joins("JOIN organization_members ON users.id = organization_members.user_id").
		Where("organization_members.organization_id IN ? AND users.id != ? AND users.status = ?", orgIDs, userID, "active").
		Group("users.id").
		Select("users.*").
		Find(&users).Error

	return users, err
}

// CreateSystemMessage 创建系统消息
func (s *ChatService) CreateSystemMessage(chatID uint, messageContent string) (*models.Message, error) {
	message := &models.Message{
		ChatID:    chatID,
		SenderID:  nil, // 系统消息没有发送者
		Type:      "system",
		Content:   &messageContent,
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}

	if err := database.DB.Create(message).Error; err != nil {
		return nil, err
	}

	// 更新聊天室的更新时间
	database.DB.Model(&models.Chat{}).Where("id = ?", chatID).Update("updated_at", time.Now())

	return message, nil
}

// GetUserBasicInfo 获取用户基本信息
func (s *ChatService) GetUserBasicInfo(userID uint, result interface{}) error {
	return database.DB.Model(&models.User{}).
		Select("first_name, last_name").
		Where("id = ?", userID).
		Scan(result).Error
}
