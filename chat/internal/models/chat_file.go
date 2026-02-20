package models

import (
	"time"
)

// ChatFile 聊天文件模型
type ChatFile struct {
	ID         uint      `gorm:"primaryKey" json:"id"`
	ChatID     uint      `gorm:"not null" json:"chat_id"`
	MessageID  uint      `gorm:"not null" json:"message_id"`
	FileType   string    `gorm:"type:enum('document','image','video');not null" json:"file_type"`
	FileURL    string    `gorm:"type:varchar(500);not null" json:"file_url"`
	FileName   string    `gorm:"type:varchar(255);not null" json:"file_name"`
	FileSize   int64     `gorm:"type:bigint;not null" json:"file_size"`
	UploadedBy uint      `gorm:"not null" json:"uploaded_by"`
	CreatedAt  time.Time `json:"created_at"`

	// 关联关系
	Chat     Chat    `gorm:"foreignKey:ChatID" json:"chat,omitempty"`
	Message  Message `gorm:"foreignKey:MessageID" json:"message,omitempty"`
	Uploader User    `gorm:"foreignKey:UploadedBy" json:"uploader,omitempty"`
}

// TableName 指定表名
func (ChatFile) TableName() string {
	return "chat_files"
}
