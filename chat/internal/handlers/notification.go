package handlers

import (
	"kelisim-chat/internal/middleware"
	"kelisim-chat/internal/services"
	"net/http"

	"github.com/gin-gonic/gin"
)

// NotificationHandler 推送通知处理器
type NotificationHandler struct {
	notificationService *services.NotificationService
}

// NewNotificationHandler 创建推送通知处理器
func NewNotificationHandler() *NotificationHandler {
	return &NotificationHandler{
		notificationService: services.NewNotificationService(),
	}
}

// RegisterDeviceRequest 注册设备请求
type RegisterDeviceRequest struct {
	Token    string `json:"token" binding:"required"`
	Platform string `json:"platform" binding:"required,oneof=ios android web"`
	DeviceID string `json:"device_id"`
}

// RegisterDevice 注册设备 Token
func (h *NotificationHandler) RegisterDevice(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	var req RegisterDeviceRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	err := h.notificationService.RegisterDeviceToken(userID, req.Token, req.Platform, req.DeviceID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to register device"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "Device registered successfully",
	})
}

// UnregisterDevice 注销设备 Token
func (h *NotificationHandler) UnregisterDevice(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	var req struct {
		Token string `json:"token" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	err := h.notificationService.UnregisterDeviceToken(userID, req.Token)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to unregister device"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "Device unregistered successfully",
	})
}

// GetDeviceTokens 获取用户的设备 Token 列表
func (h *NotificationHandler) GetDeviceTokens(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	tokens, err := h.notificationService.GetUserDeviceTokens(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get device tokens"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"tokens": tokens,
	})
}

