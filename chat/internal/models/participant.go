package models

import (
	"time"
)

// ChatParticipant 聊天参与者模型
type ChatParticipant struct {
	ID         uint       `gorm:"primaryKey" json:"id"`
	ChatID     uint       `gorm:"not null" json:"chat_id"`
	UserID     uint       `gorm:"not null" json:"user_id"`
	Role       *string    `gorm:"type:varchar(50)" json:"role,omitempty"`
	JoinedAt   time.Time  `gorm:"default:CURRENT_TIMESTAMP" json:"joined_at"`
	LastReadAt *time.Time `json:"last_read_at,omitempty"`
	CreatedAt  time.Time  `json:"created_at"`
	UpdatedAt  time.Time  `json:"updated_at"`

	// 关联关系
	Chat Chat `gorm:"foreignKey:ChatID" json:"chat,omitempty"`
	User User `gorm:"foreignKey:UserID" json:"user,omitempty"`
}

// TableName 指定表名
func (ChatParticipant) TableName() string {
	return "chat_participants"
}
