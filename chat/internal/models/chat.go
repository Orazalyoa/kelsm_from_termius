package models

import (
	"time"

	"gorm.io/gorm"
)

// Chat 聊天室模型
type Chat struct {
	ID        uint           `gorm:"primaryKey" json:"id"`
	Title     string         `gorm:"type:varchar(255);not null" json:"title"`
	Type      string         `gorm:"type:enum('private','group');default:'group'" json:"type"`
	CreatedBy uint           `gorm:"not null" json:"created_by"`
	CreatedAt time.Time      `json:"created_at"`
	UpdatedAt time.Time      `json:"updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"index" json:"-"`

	// 关联关系
	Creator      User              `gorm:"foreignKey:CreatedBy" json:"creator,omitempty"`
	Participants []ChatParticipant `gorm:"foreignKey:ChatID" json:"participants,omitempty"`
	Messages     []Message         `gorm:"foreignKey:ChatID" json:"messages,omitempty"`
	Files        []ChatFile        `gorm:"foreignKey:ChatID" json:"files,omitempty"`
}

// TableName 指定表名
func (Chat) TableName() string {
	return "chats"
}
