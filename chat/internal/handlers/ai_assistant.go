package handlers

import (
	"kelisim-chat/internal/middleware"
	"kelisim-chat/internal/services"
	"kelisim-chat/internal/websocket"
	"net/http"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
)

// AIAssistantHandler AI助手处理器
type AIAssistantHandler struct {
	llmService          *services.LLMService
	messageService      *services.MessageService
	chatService         *services.ChatService
	notificationService *services.NotificationService
	hub                 *websocket.Hub
}

// NewAIAssistantHandler 创建AI助手处理器
func NewAIAssistantHandler(hub *websocket.Hub) *AIAssistantHandler {
	return &AIAssistantHandler{
		llmService:          services.NewLLMService(),
		messageService:      services.NewMessageService(),
		chatService:         services.NewChatService(),
		notificationService: services.NewNotificationService(),
		hub:                 hub,
	}
}

// AskRequest AI提问请求
type AskRequest struct {
	Question       string `json:"question" binding:"required"`
	IncludeContext bool   `json:"include_context"` // 是否包含最近的聊天上下文
	ContextCount   int    `json:"context_count"`   // 上下文消息数量，默认10
}

// SummarizeRequest 总结请求
type SummarizeRequest struct {
	MessageCount int    `json:"message_count"` // 要总结的消息数量，0表示全部
	Language     string `json:"language"`      // 总结语言
}

// AskAI AI助手提问
func (h *AIAssistantHandler) AskAI(c *gin.Context) {
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

	// 检查用户是否在聊天室中
	if !h.chatService.IsUserInChat(uint(chatID), userID) {
		c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
		return
	}

	var req AskRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 设置默认上下文数量
	if req.ContextCount == 0 {
		req.ContextCount = 100
	}

	// 构建对话上下文
	var contextMessages []services.ChatMessage

	if req.IncludeContext {
		// 获取最近的聊天消息作为上下文
		recentMessages, err := h.messageService.GetChatMessages(uint(chatID), req.ContextCount, 0)
		if err == nil && len(recentMessages) > 0 {
			// 反转消息顺序（从旧到新）
			for i := len(recentMessages) - 1; i >= 0; i-- {
				msg := recentMessages[i]
				if msg.Type == "text" && msg.Content != nil && *msg.Content != "" {
					role := "user"
					// 如果消息是系统消息或AI助手消息，标记为assistant
					if msg.Type == "system" || msg.SenderID == nil {
						role = "assistant"
					}
					contextMessages = append(contextMessages, services.ChatMessage{
						Role:    role,
						Content: *msg.Content,
					})
				}
			}
		}
	}

	// 系统提示词
	systemPrompt := "You are a helpful AI assistant for a legal consultation platform. You help users with their questions and provide relevant information. Be professional, concise, and helpful."

	// 调用LLM获取回答
	answer, err := h.llmService.AskQuestion(req.Question, systemPrompt, contextMessages)
	if err != nil {
		logrus.WithError(err).Error("Failed to get AI response")
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get AI response"})
		return
	}

	// 将AI回答保存为消息
	aiMessage, err := h.messageService.SendMessage(
		uint(chatID),
		nil,            // AI助手没有sender_id
		"ai_assistant", // AI助手消息类型
		&answer,
		nil,
		nil,
		nil,
	)
	if err != nil {
		logrus.WithError(err).Error("Failed to save AI message")
		// 即使保存失败，也返回AI回答
		c.JSON(http.StatusOK, gin.H{
			"answer":  answer,
			"message": aiMessage,
			"warning": "AI response generated but failed to save to chat",
		})
		return
	}

	// 通过WebSocket广播AI消息
	if h.hub != nil {
		wsMessage := convertToWebSocketMessage(aiMessage)
		h.hub.BroadcastToChat(uint(chatID), websocket.ServerMessage{
			Type:    websocket.NewMessage,
			Message: wsMessage,
		}, 0) // 0表示不排除任何用户
	}

	// 获取聊天室的所有参与者并发送推送通知
	participants, err := h.chatService.GetChatParticipants(uint(chatID))
	if err == nil {
		var recipientUserIDs []uint
		for _, p := range participants {
			recipientUserIDs = append(recipientUserIDs, p.UserID)
		}
		// 发送推送通知（异步）
		go h.notificationService.SendChatMessageNotification(aiMessage, recipientUserIDs)
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "AI response generated successfully",
		"data":    aiMessage,
		"answer":  answer,
	})
}

// OperatorAskAI AI助手提问 - Operator专用（不保存消息）
func (h *AIAssistantHandler) OperatorAskAI(c *gin.Context) {
	operatorID, exists := middleware.GetOperatorIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Operator not authenticated"})
		return
	}

	chatIDStr := c.Param("id")
	chatID, err := strconv.ParseUint(chatIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid chat ID"})
		return
	}

	var req AskRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 设置默认上下文数量
	if req.ContextCount == 0 {
		req.ContextCount = 100
	}

	// 构建对话上下文
	var contextMessages []services.ChatMessage

	if req.IncludeContext {
		// 获取最近的聊天消息作为上下文
		recentMessages, err := h.messageService.GetChatMessages(uint(chatID), req.ContextCount, 0)
		if err == nil && len(recentMessages) > 0 {
			// 反转消息顺序（从旧到新）
			for i := len(recentMessages) - 1; i >= 0; i-- {
				msg := recentMessages[i]
				if msg.Type == "text" && msg.Content != nil && *msg.Content != "" {
					role := "user"
					// 如果消息是系统消息，标记为assistant
					if msg.Type == "system" || msg.SenderID == nil {
						role = "assistant"
					}
					contextMessages = append(contextMessages, services.ChatMessage{
						Role:    role,
						Content: *msg.Content,
					})
				}
			}
		}
	}

	// 系统提示词
	systemPrompt := "You are a helpful AI assistant for operators managing a legal consultation platform. You help operators understand conversations and provide insights. Be professional, concise, and helpful."

	// 调用LLM获取回答
	answer, err := h.llmService.AskQuestion(req.Question, systemPrompt, contextMessages)
	if err != nil {
		logrus.WithError(err).Error("Failed to get AI response for operator")
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get AI response"})
		return
	}

	// 直接返回 AI 分析结果，不保存到数据库
	c.JSON(http.StatusOK, gin.H{
		"answer":      answer,
		"timestamp":   time.Now(),
		"operator_id": operatorID,
		"context_used": req.IncludeContext,
	})
}

// OperatorSummarize 总结聊天记录 - Operator专用
func (h *AIAssistantHandler) OperatorSummarize(c *gin.Context) {
	operatorID, exists := middleware.GetOperatorIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Operator not authenticated"})
		return
	}

	chatIDStr := c.Param("id")
	chatID, err := strconv.ParseUint(chatIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid chat ID"})
		return
	}

	var req SummarizeRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		// 如果没有提供请求体，使用默认值
		req.MessageCount = 50
		req.Language = "en"
	}

	// 设置默认值
	if req.MessageCount == 0 {
		req.MessageCount = 50
	}
	if req.Language == "" {
		req.Language = "en"
	}

	// 获取聊天消息
	messages, err := h.messageService.GetChatMessages(uint(chatID), req.MessageCount, 0)
	if err != nil {
		logrus.WithError(err).Error("Failed to get chat messages")
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get chat messages"})
		return
	}

	if len(messages) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"summary": "No messages to summarize",
		})
		return
	}

	// 构建对话历史
	var conversationMessages []services.ChatMessage
	for i := len(messages) - 1; i >= 0; i-- {
		msg := messages[i]
		if msg.Type == "text" && msg.Content != nil && *msg.Content != "" {
			role := "user"
			if msg.Type == "system" || msg.SenderID == nil {
				role = "assistant"
			}

			// 构建消息内容（包含发送者信息）
			content := *msg.Content
			if msg.Sender != nil {
				senderName := msg.Sender.GetFullName()
				if senderName != "" {
					content = senderName + ": " + content
				}
			}

			conversationMessages = append(conversationMessages, services.ChatMessage{
				Role:    role,
				Content: content,
			})
		}
	}

	// 调用LLM生成总结
	summary, err := h.llmService.SummarizeConversation(conversationMessages, req.Language)
	if err != nil {
		logrus.WithError(err).Error("Failed to generate summary")
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to generate summary"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"summary":       summary,
		"message_count": len(messages),
		"language":      req.Language,
		"summarized_at": time.Now(),
		"operator_id":   operatorID,
	})
}

// OperatorAnalyzeFiles 分析聊天文件 - Operator专用
func (h *AIAssistantHandler) OperatorAnalyzeFiles(c *gin.Context) {
	operatorID, exists := middleware.GetOperatorIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Operator not authenticated"})
		return
	}

	chatIDStr := c.Param("id")
	chatID, err := strconv.ParseUint(chatIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid chat ID"})
		return
	}

	type AnalyzeFilesRequest struct {
		Question string `json:"question"` // 关于文件的问题
		FileIDs  []uint `json:"file_ids"` // 要分析的文件ID列表
	}

	var req AnalyzeFilesRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if req.Question == "" {
		req.Question = "Please analyze and summarize the files in this chat."
	}

	// TODO: 实现文件分析逻辑
	// 1. 获取指定的文件信息
	// 2. 读取文件内容（PDF、图片OCR等）
	// 3. 将文件内容发送给 LLM 分析
	// 4. 返回分析结果

	// 临时实现：返回占位符
	c.JSON(http.StatusOK, gin.H{
		"analysis":    "File analysis feature coming soon. This will analyze PDFs, images, and documents in the chat.",
		"chat_id":     chatID,
		"operator_id": operatorID,
		"timestamp":   time.Now(),
		"note":        "This feature requires additional implementation for file parsing",
	})
}

// SummarizeChat 总结聊天记录 - 已废弃，使用 OperatorSummarize
// 保留用于向后兼容，但不再在路由中注册
func (h *AIAssistantHandler) SummarizeChat(c *gin.Context) {
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

	// 检查用户是否在聊天室中
	if !h.chatService.IsUserInChat(uint(chatID), userID) {
		c.JSON(http.StatusForbidden, gin.H{"error": "Access denied"})
		return
	}

	var req SummarizeRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		// 如果没有提供请求体，使用默认值
		req.MessageCount = 50
		req.Language = "en"
	}

	// 设置默认值
	if req.MessageCount == 0 {
		req.MessageCount = 50
	}
	if req.Language == "" {
		req.Language = "en"
	}

	// 获取聊天消息
	messages, err := h.messageService.GetChatMessages(uint(chatID), req.MessageCount, 0)
	if err != nil {
		logrus.WithError(err).Error("Failed to get chat messages")
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get chat messages"})
		return
	}

	if len(messages) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"summary": "No messages to summarize",
		})
		return
	}

	// 构建对话历史
	var conversationMessages []services.ChatMessage
	for i := len(messages) - 1; i >= 0; i-- {
		msg := messages[i]
		if msg.Type == "text" && msg.Content != nil && *msg.Content != "" {
			role := "user"
			if msg.Type == "system" || msg.SenderID == nil {
				role = "assistant"
			}

			// 构建消息内容（包含发送者信息）
			content := *msg.Content
			if msg.Sender != nil {
				senderName := msg.Sender.GetFullName()
				if senderName != "" {
					content = senderName + ": " + content
				}
			}

			conversationMessages = append(conversationMessages, services.ChatMessage{
				Role:    role,
				Content: content,
			})
		}
	}

	// 调用LLM生成总结
	summary, err := h.llmService.SummarizeConversation(conversationMessages, req.Language)
	if err != nil {
		logrus.WithError(err).Error("Failed to generate summary")
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to generate summary"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"summary":       summary,
		"message_count": len(messages),
		"language":      req.Language,
		"summarized_at": messages[0].CreatedAt,
	})
}
