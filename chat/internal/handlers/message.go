package handlers

import (
	"kelisim-chat/internal/middleware"
	"kelisim-chat/internal/models"
	"kelisim-chat/internal/services"
	"kelisim-chat/internal/websocket"
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"
)

// MessageHandler 消息处理器
type MessageHandler struct {
	messageService      *services.MessageService
	chatService         *services.ChatService
	notificationService *services.NotificationService
	hub                 *websocket.Hub
}

// NewMessageHandler 创建消息处理器
func NewMessageHandler(hub *websocket.Hub) *MessageHandler {
	return &MessageHandler{
		messageService:      services.NewMessageService(),
		chatService:         services.NewChatService(),
		notificationService: services.NewNotificationService(),
		hub:                 hub,
	}
}

// SendMessageRequest 发送消息请求
type SendMessageRequest struct {
	Content string `json:"content"`
	Type    string `json:"type" binding:"required,oneof=text document image system ai_assistant"`
}

// GetMessages 获取聊天消息
func (h *MessageHandler) GetMessages(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	chatIDStr := c.Param("id")
	chatID, err := strconv.ParseUint(chatIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid chat ID"})
		return
	}

	// 检查用户是否在聊天室中（Operator 跳过检查）
	isOperator, _ := c.Get("is_operator")
	if isOp, ok := isOperator.(bool); !ok || !isOp {
		// 非 Operator，需要检查权限
		if !h.chatService.IsUserInChat(uint(chatID), userID) {
			c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
			return
		}
	}

	// 获取分页参数
	limitStr := c.DefaultQuery("limit", "20")
	offsetStr := c.DefaultQuery("offset", "0")

	limit, err := strconv.Atoi(limitStr)
	if err != nil || limit <= 0 || limit > 100 {
		limit = 20
	}

	offset, err := strconv.Atoi(offsetStr)
	if err != nil || offset < 0 {
		offset = 0
	}

	messages, err := h.messageService.GetChatMessages(uint(chatID), limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get messages"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"messages": messages,
		"limit":    limit,
		"offset":   offset,
	})
}

// SendMessage 发送消息
func (h *MessageHandler) SendMessage(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	chatIDStr := c.Param("id")
	chatID, err := strconv.ParseUint(chatIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid chat ID"})
		return
	}

	// 检查是否是 Operator
	isOperator, _ := c.Get("is_operator")
	isOp, _ := isOperator.(bool)

	// 检查用户是否在聊天室中（Operator 跳过检查）
	if !isOp {
		if !h.chatService.IsUserInChat(uint(chatID), userID) {
			c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
			return
		}
	}

	var req SendMessageRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 验证消息内容
	if req.Type == "text" && req.Content == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Content is required for text messages"})
		return
	}

	// 如果是 Operator，发送系统消息（sender_id = nil）
	var senderID *uint
	if !isOp {
		senderID = &userID
	} else {
		senderID = nil // Operator 发送的是系统消息
	}

	message, err := h.messageService.SendMessage(uint(chatID), senderID, req.Type, &req.Content, nil, nil, nil)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to send message"})
		return
	}

	// 获取聊天室的所有参与者
	participants, err := h.chatService.GetChatParticipants(uint(chatID))
	if err == nil {
		var recipientUserIDs []uint
		for _, p := range participants {
			recipientUserIDs = append(recipientUserIDs, p.UserID)
		}

		// 发送推送通知（异步，不影响响应速度）
		// 优先使用 FCM V1 API
		fcmService := services.GetFCMService()
		if fcmService != nil {
			go fcmService.SendChatMessageNotification(message, recipientUserIDs)
		} else {
			// 降级到 Legacy API
			go h.notificationService.SendChatMessageNotification(message, recipientUserIDs)
		}
	}

	// 通过 WebSocket 广播新消息（排除发送者）
	if h.hub != nil {
		wsMessage := convertToWebSocketMessage(message)
		h.hub.BroadcastToChat(uint(chatID), websocket.ServerMessage{
			Type:    websocket.NewMessage,
			Message: wsMessage,
		}, userID)
	}

	c.JSON(http.StatusCreated, gin.H{
		"message": "Message sent successfully",
		"data":    message,
	})
}

// MarkAsRead 标记消息为已读
func (h *MessageHandler) MarkAsRead(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	messageIDStr := c.Param("id")
	messageID, err := strconv.ParseUint(messageIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid message ID"})
		return
	}

	err = h.messageService.MarkAsRead(uint(messageID), userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to mark message as read"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "Message marked as read",
	})
}

// MarkChatAsRead 标记整个聊天室为已读
func (h *MessageHandler) MarkChatAsRead(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	chatIDStr := c.Param("id")
	chatID, err := strconv.ParseUint(chatIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid chat ID"})
		return
	}

	// 检查是否是 Operator（Operator 不需要标记已读，直接返回成功）
	isOperator, _ := c.Get("is_operator")
	if isOp, ok := isOperator.(bool); ok && isOp {
		c.JSON(http.StatusOK, gin.H{
			"message": "Operator does not need to mark as read",
		})
		return
	}

	// 检查用户是否在聊天室中
	if !h.chatService.IsUserInChat(uint(chatID), userID) {
		c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
		return
	}

	err = h.messageService.MarkChatAsRead(uint(chatID), userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to mark chat as read"})
		return
	}

	// 更新最后读取时间
	h.chatService.UpdateLastReadAt(uint(chatID), userID)

	c.JSON(http.StatusOK, gin.H{
		"message": "Chat marked as read",
	})
}

// DeleteMessage 删除消息
func (h *MessageHandler) DeleteMessage(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	messageIDStr := c.Param("id")
	messageID, err := strconv.ParseUint(messageIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid message ID"})
		return
	}

	err = h.messageService.DeleteMessage(uint(messageID), userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to delete message"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "Message deleted successfully",
	})
}

// GetUnreadCount 获取未读消息数量
func (h *MessageHandler) GetUnreadCount(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	count, err := h.messageService.GetUnreadCount(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get unread count"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"unread_count": count,
	})
}

// convertToWebSocketMessage 将 models.Message 转换为 websocket.Message
func convertToWebSocketMessage(msg *models.Message) *websocket.Message {
	wsMsg := &websocket.Message{
		ID:        msg.ID,
		ChatID:    msg.ChatID,
		Type:      msg.Type,
		Content:   msg.Content,
		FileURL:   msg.FileURL,
		FileName:  msg.FileName,
		FileSize:  msg.FileSize,
		CreatedAt: msg.CreatedAt.Format("2006-01-02T15:04:05.000Z07:00"),
		Status:    "sent",
	}

	// 转换发送者信息
	if msg.Sender != nil {
		wsMsg.Sender = &websocket.User{
			ID:        msg.Sender.ID,
			FirstName: msg.Sender.FirstName,
			LastName:  msg.Sender.LastName,
			FullName:  msg.Sender.GetFullName(),
			Avatar:    msg.Sender.Avatar,
			UserType:  msg.Sender.UserType,
			Status:    msg.Sender.Status,
		}
	}

	return wsMsg
}
