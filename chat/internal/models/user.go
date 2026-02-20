package models

import (
	"time"

	"gorm.io/gorm"
)

// User 用户模型 (关联 Laravel users 表)
type User struct {
	ID              uint           `gorm:"primaryKey" json:"id"`
	UserType        string         `gorm:"type:enum('company_admin','expert','lawyer');not null" json:"user_type"`
	Email           *string        `gorm:"type:varchar(255);uniqueIndex" json:"email,omitempty"`
	Phone           *string        `gorm:"type:varchar(255);uniqueIndex" json:"phone,omitempty"`
	CountryCode     *string        `gorm:"type:varchar(10)" json:"country_code,omitempty"`
	Password        string         `gorm:"type:varchar(255);not null" json:"-"`
	FirstName       string         `gorm:"type:varchar(255);not null" json:"first_name"`
	LastName        string         `gorm:"type:varchar(255);not null" json:"last_name"`
	Gender          *string        `gorm:"type:enum('male','female','other')" json:"gender,omitempty"`
	Avatar          *string        `gorm:"type:varchar(255)" json:"avatar,omitempty"`
	Locale          string         `gorm:"type:varchar(10);default:'ru'" json:"locale"`
	Status          string         `gorm:"type:enum('active','inactive','suspended');default:'active'" json:"status"`
	EmailVerifiedAt *time.Time     `json:"email_verified_at,omitempty"`
	PhoneVerifiedAt *time.Time     `json:"phone_verified_at,omitempty"`
	LastLoginAt     *time.Time     `json:"last_login_at,omitempty"`
	LastLoginIP     *string        `gorm:"type:varchar(45)" json:"last_login_ip,omitempty"`
	CreatedAt       time.Time      `json:"created_at"`
	UpdatedAt       time.Time      `json:"updated_at"`
	DeletedAt       gorm.DeletedAt `gorm:"index" json:"-"`

	// 关联关系
	Chats        []Chat          `gorm:"many2many:chat_participants;" json:"chats,omitempty"`
	Messages     []Message       `gorm:"foreignKey:SenderID" json:"messages,omitempty"`
	MessageStats []MessageStatus `gorm:"foreignKey:UserID" json:"message_stats,omitempty"`
	ChatFiles    []ChatFile      `gorm:"foreignKey:UploadedBy" json:"chat_files,omitempty"`
}

// TableName 指定表名
func (User) TableName() string {
	return "users"
}

// GetFullName 获取用户全名
func (u *User) GetFullName() string {
	return u.FirstName + " " + u.LastName
}

// GetDisplayName 获取显示名称（优先使用全名）
func (u *User) GetDisplayName() string {
	return u.GetFullName()
}

// IsCompanyAdmin 检查是否为公司管理员
func (u *User) IsCompanyAdmin() bool {
	return u.UserType == "company_admin"
}

// IsExpert 检查是否为专家
func (u *User) IsExpert() bool {
	return u.UserType == "expert"
}

// IsLawyer 检查是否为律师
func (u *User) IsLawyer() bool {
	return u.UserType == "lawyer"
}

// IsActive 检查用户是否激活
func (u *User) IsActive() bool {
	return u.Status == "active"
}

// CanCreateConsultations 检查是否可以创建咨询
func (u *User) CanCreateConsultations() bool {
	return !u.IsLawyer() // 律师不能创建咨询
}

// GetAvatarURL 获取头像URL
func (u *User) GetAvatarURL() *string {
	if u.Avatar != nil && *u.Avatar != "" {
		// 这里应该根据实际的文件存储配置来构建URL
		avatarURL := "http://localhost:8000/storage/" + *u.Avatar
		return &avatarURL
	}
	return nil
}
