package handlers

import (
	"encoding/json"
	"kelisim-chat/internal/middleware"
	"kelisim-chat/internal/services"
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"
)

// ChatHandler 聊天处理器
type ChatHandler struct {
	chatService *services.ChatService
}

// NewChatHandler 创建聊天处理器
func NewChatHandler() *ChatHandler {
	return &ChatHandler{
		chatService: services.NewChatService(),
	}
}

// CreateChatRequest 创建聊天请求
type CreateChatRequest struct {
	Title          string `json:"title" binding:"required"`
	Type           string `json:"type" binding:"required,oneof=private group"`
	ParticipantIDs []uint `json:"participant_ids"`
}

// GetChats 获取用户的聊天列表
func (h *ChatHandler) GetChats(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	// 检查是否是 Operator
	isOperator, _ := c.Get("is_operator")
	if isOp, ok := isOperator.(bool); ok && isOp {
		// Operator 可以看到所有聊天
		chats, err := h.chatService.GetAllChats()
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get chats"})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"chats": chats,
		})
		return
	}

	// 普通用户只能看到自己参与的聊天
	chats, err := h.chatService.GetUserChats(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get chats"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"chats": chats,
	})
}

// CreateChat 创建聊天室
func (h *ChatHandler) CreateChat(c *gin.Context) {
	userID, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	var req CreateChatRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	chat, err := h.chatService.CreateChat(req.Title, req.Type, userID, req.ParticipantIDs)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to create chat"})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"message": "Chat created successfully",
		"chat":    chat,
	})
}

// GetChat 获取聊天详情
func (h *ChatHandler) GetChat(c *gin.Context) {
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
	if isOp, ok := isOperator.(bool); ok && isOp {
		// Operator 可以访问任何聊天
		chat, err := h.chatService.GetChatByIDForOperator(uint(chatID))
		if err != nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "Chat not found"})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"chat": chat,
		})
		return
	}

	// 普通用户只能访问自己参与的聊天
	chat, err := h.chatService.GetChatByID(uint(chatID), userID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "Chat not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"chat": chat,
	})
}

// GetChatParticipants 获取聊天参与者
func (h *ChatHandler) GetChatParticipants(c *gin.Context) {
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
		if !h.chatService.IsUserInChat(uint(chatID), userID) {
			c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
			return
		}
	}

	participants, err := h.chatService.GetChatParticipants(uint(chatID))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get participants"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"participants": participants,
	})
}

// AddParticipantRequest 添加参与者请求
type AddParticipantRequest struct {
	UserID uint   `json:"user_id" binding:"required"`
	Role   string `json:"role"`
}

// AddParticipant 添加参与者
func (h *ChatHandler) AddParticipant(c *gin.Context) {
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

	// 检查当前用户是否为公司管理员或 Operator
	if isOp {
		// Operator 有权限添加参与者
	} else {
		// 检查当前用户是否为公司管理员
		isAdmin, err := h.chatService.IsCompanyAdmin(userID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to verify user permissions"})
			return
		}
		if !isAdmin {
			c.JSON(http.StatusForbidden, gin.H{"error": "Only company administrators can add participants"})
			return
		}
	}

	var req AddParticipantRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Operator 跳过组织检查
	if !isOp {
		// 检查要添加的用户和当前用户是否在同一组织
		sameOrg, err := h.chatService.AreUsersInSameOrganization(userID, req.UserID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to verify organization"})
			return
		}
		if !sameOrg {
			c.JSON(http.StatusForbidden, gin.H{"error": "Can only add users from your organization"})
			return
		}
	}

	err = h.chatService.AddParticipant(uint(chatID), req.UserID, req.Role)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to add participant"})
		return
	}

	// 获取新添加的用户信息
	var newUser struct {
		FirstName string
		LastName  string
	}
	h.chatService.GetUserBasicInfo(req.UserID, &newUser)

	// 创建系统消息
	systemMessageData := map[string]interface{}{
		"type": "user_joined",
		"data": map[string]interface{}{
			"user_id":    req.UserID,
			"user_name":  newUser.FirstName + " " + newUser.LastName,
			"first_name": newUser.FirstName,
			"last_name":  newUser.LastName,
		},
	}
	systemMessageJSON, _ := json.Marshal(systemMessageData)
	h.chatService.CreateSystemMessage(uint(chatID), string(systemMessageJSON))

	c.JSON(http.StatusOK, gin.H{
		"message": "Participant added successfully",
	})
}

// RemoveParticipant 移除参与者
func (h *ChatHandler) RemoveParticipant(c *gin.Context) {
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

	participantIDStr := c.Param("userId")
	participantID, err := strconv.ParseUint(participantIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid participant ID"})
		return
	}

	// 检查用户是否在聊天室中（Operator 跳过检查）
	isOperator, _ := c.Get("is_operator")
	if isOp, ok := isOperator.(bool); !ok || !isOp {
		if !h.chatService.IsUserInChat(uint(chatID), userID) {
			c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
			return
		}
	}

	err = h.chatService.RemoveParticipant(uint(chatID), uint(participantID))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to remove participant"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "Participant removed successfully",
	})
}

// GetOrganizationMembers 获取用户组织的成员列表
func (h *ChatHandler) GetOrganizationMembers(c *gin.Context) {
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
		if !h.chatService.IsUserInChat(uint(chatID), userID) {
			c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
			return
		}
	}

	// 获取组织成员列表（Operator 可能返回空列表）
	members, err := h.chatService.GetOrganizationMembers(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get organization members"})
		return
	}

	// 过滤掉已经在聊天室中的成员
	participants, err := h.chatService.GetChatParticipants(uint(chatID))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get chat participants"})
		return
	}

	// 创建参与者ID的映射
	participantMap := make(map[uint]bool)
	for _, p := range participants {
		participantMap[p.UserID] = true
	}

	// 过滤结果
	availableMembers := make([]gin.H, 0)
	for _, member := range members {
		if !participantMap[member.ID] {
			availableMembers = append(availableMembers, gin.H{
				"id":         member.ID,
				"first_name": member.FirstName,
				"last_name":  member.LastName,
				"full_name":  member.GetFullName(),
				"email":      member.Email,
				"avatar":     member.Avatar,
				"user_type":  member.UserType,
			})
		}
	}

	c.JSON(http.StatusOK, gin.H{
		"members": availableMembers,
	})
}
