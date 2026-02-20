package router

import (
	"kelisim-chat/internal/handlers"
	"kelisim-chat/internal/middleware"
	"kelisim-chat/internal/websocket"
	"net/http"

	"github.com/gin-gonic/gin"
)

// SetupRouter 设置路由
func SetupRouter() *gin.Engine {
	// 设置 Gin 模式
	gin.SetMode(gin.ReleaseMode)

	r := gin.Default()

	// 中间件
	r.Use(middleware.CORSMiddleware())
	r.Use(gin.Logger())
	r.Use(gin.Recovery())

	// 静态文件服务
	r.Static("/storage/chat-files", "./storage/chat-files")

	// 健康检查
	r.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status":  "ok",
			"service": "kelisim-chat",
		})
	})

	// 创建 WebSocket Hub
	hub := websocket.NewHub()
	go hub.Run()

	// 创建处理器
	chatHandler := handlers.NewChatHandler()
	messageHandler := handlers.NewMessageHandler(hub)
	fileHandler := handlers.NewFileHandler(hub)
	wsHandler := handlers.NewWebSocketHandler(hub)
	notificationHandler := handlers.NewNotificationHandler()
	aiAssistantHandler := handlers.NewAIAssistantHandler(hub)

	// WebSocket 路由
	r.GET("/ws", wsHandler.HandleWebSocket)

	// API 路由组
	api := r.Group("/api")
	{
		// Operator (Admin) routes - 使用独立的认证中间件
		operator := api.Group("/operator")
		operator.Use(middleware.OperatorAuthMiddleware())
		{
			// Get all chats (operators can see all chats)
			operator.GET("/chats", chatHandler.GetChats)

			// Send message as operator (system message)
			operator.POST("/chats/:id/messages", messageHandler.SendMessage)

			// AI assistant tools (only for operators, no messages saved to chat)
			operator.POST("/chats/:id/ai/ask", aiAssistantHandler.OperatorAskAI)
			operator.POST("/chats/:id/ai/summarize", aiAssistantHandler.OperatorSummarize)
			operator.POST("/chats/:id/ai/analyze-files", aiAssistantHandler.OperatorAnalyzeFiles)
		}

		// 需要认证的路由（普通用户）
		auth := api.Group("")
		auth.Use(middleware.AuthMiddleware())
		{
			// 聊天管理
			chats := auth.Group("/chats")
			{
				chats.GET("", chatHandler.GetChats)
				chats.POST("", chatHandler.CreateChat)
				chats.GET("/:id", chatHandler.GetChat)
				chats.GET("/:id/participants", chatHandler.GetChatParticipants)
				chats.POST("/:id/participants", chatHandler.AddParticipant)
				chats.DELETE("/:id/participants/:userId", chatHandler.RemoveParticipant)
				chats.GET("/:id/available-members", chatHandler.GetOrganizationMembers)

				// AI routes removed - now available only to operators
			}

			// 消息管理
			messages := auth.Group("/chats/:id/messages")
			{
				messages.GET("", messageHandler.GetMessages)
				messages.POST("", messageHandler.SendMessage)
			}

			// 消息状态
			messageStatus := auth.Group("/messages")
			{
				messageStatus.PUT("/:id/status", messageHandler.MarkAsRead)
				messageStatus.DELETE("/:id", messageHandler.DeleteMessage)
			}

			// 聊天已读
			auth.PUT("/chats/:id/read", messageHandler.MarkChatAsRead)

			// 未读消息数量
			auth.GET("/unread-count", messageHandler.GetUnreadCount)

			// 文件管理
			files := auth.Group("/chats/:id/files")
			{
				files.POST("", fileHandler.UploadFile)
				files.GET("", fileHandler.GetChatFiles)
			}

			// 文件下载
			auth.GET("/files/:id/download", fileHandler.DownloadFile)

			// 推送通知
			notifications := auth.Group("/notifications")
			{
				notifications.POST("/register", notificationHandler.RegisterDevice)
				notifications.POST("/unregister", notificationHandler.UnregisterDevice)
				notifications.GET("/devices", notificationHandler.GetDeviceTokens)
			}
		}
	}

	return r
}
