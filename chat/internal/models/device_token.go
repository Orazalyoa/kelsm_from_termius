package models

import "time"

// DeviceToken 设备推送 Token
type DeviceToken struct {
	ID        uint      `gorm:"primaryKey" json:"id"`
	UserID    uint      `gorm:"index;not null" json:"user_id"`
	Token     string    `gorm:"type:varchar(500);not null;index" json:"token"`
	Platform  string    `gorm:"type:varchar(20);not null" json:"platform"` // ios, android, web
	DeviceID  string    `gorm:"type:varchar(255)" json:"device_id"`
	IsActive  bool      `gorm:"default:true" json:"is_active"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
	
	// 关联
	User *User `gorm:"foreignKey:UserID" json:"user,omitempty"`
}

// TableName 指定表名
func (DeviceToken) TableName() string {
	return "device_tokens"
}

