package models

import (
	"time"

	"gorm.io/gorm"
)

// Message 消息模型
type Message struct {
	ID        uint           `gorm:"primaryKey" json:"id"`
	ChatID    uint           `gorm:"not null" json:"chat_id"`
	SenderID  *uint          `gorm:"index" json:"sender_id,omitempty"`
	Type      string         `gorm:"type:enum('text','document','image','system','ai_assistant');default:'text'" json:"type"`
	Content   *string        `gorm:"type:text" json:"content,omitempty"`
	FileURL   *string        `gorm:"type:varchar(500)" json:"file_url,omitempty"`
	FileName  *string        `gorm:"type:varchar(255)" json:"file_name,omitempty"`
	FileSize  *int64         `gorm:"type:bigint" json:"file_size,omitempty"`
	CreatedAt time.Time      `json:"created_at"`
	UpdatedAt time.Time      `json:"updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"index" json:"-"`

	// 关联关系
	Chat      Chat            `gorm:"foreignKey:ChatID" json:"chat,omitempty"`
	Sender    *User           `gorm:"foreignKey:SenderID" json:"sender,omitempty"`
	Statuses  []MessageStatus `gorm:"foreignKey:MessageID" json:"statuses,omitempty"`
	ChatFiles []ChatFile      `gorm:"foreignKey:MessageID" json:"chat_files,omitempty"`
}

// TableName 指定表名
func (Message) TableName() string {
	return "messages"
}

// MessageStatus 消息状态模型
type MessageStatus struct {
	ID        uint      `gorm:"primaryKey" json:"id"`
	MessageID uint      `gorm:"not null" json:"message_id"`
	UserID    uint      `gorm:"not null" json:"user_id"`
	Status    string    `gorm:"type:enum('sent','delivered','read','failed');default:'sent'" json:"status"`
	UpdatedAt time.Time `json:"updated_at"`

	// 关联关系
	Message Message `gorm:"foreignKey:MessageID" json:"message,omitempty"`
	User    User    `gorm:"foreignKey:UserID" json:"user,omitempty"`
}

// TableName 指定表名
func (MessageStatus) TableName() string {
	return "message_status"
}
