package handlers

import (
	"kelisim-chat/internal/config"
	"kelisim-chat/internal/middleware"
	"kelisim-chat/internal/services"
	"kelisim-chat/internal/websocket"
	"net/http"
	"os"
	"path/filepath"
	"strconv"

	"github.com/gin-gonic/gin"
)

// FileHandler 文件处理器
type FileHandler struct {
	fileService    *services.FileService
	messageService *services.MessageService
	chatService    *services.ChatService
	hub            *websocket.Hub
}

// NewFileHandler 创建文件处理器
func NewFileHandler(hub *websocket.Hub) *FileHandler {
	return &FileHandler{
		fileService:    services.NewFileService(),
		messageService: services.NewMessageService(),
		chatService:    services.NewChatService(),
		hub:            hub,
	}
}

// UploadFile 上传文件
func (h *FileHandler) UploadFile(c *gin.Context) {
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

	// 获取上传的文件
	file, err := c.FormFile("file")
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "No file uploaded"})
		return
	}

	// 上传文件
	fileURL, fileName, fileSize, err := h.fileService.UploadFile(file, uint(chatID))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to upload file"})
		return
	}

	// 确定文件类型
	fileType := h.fileService.GetFileType(fileName)

	// 创建消息记录
	messageType := "document"
	if fileType == "image" {
		messageType = "image"
	}

	// 如果是 Operator，发送系统消息（sender_id = nil）
	var senderID *uint
	if !isOp {
		senderID = &userID
	} else {
		senderID = nil // Operator 上传的文件是系统文件
	}

	message, err := h.messageService.SendMessage(uint(chatID), senderID, messageType, nil, &fileURL, &fileName, &fileSize)
	if err != nil {
		// 如果消息创建失败，删除已上传的文件
		h.fileService.DeleteFile(fileURL)
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to create message"})
		return
	}

	// 通过 WebSocket 广播文件消息（排除发送者）
	if h.hub != nil {
		wsMessage := &websocket.Message{
			ID:        message.ID,
			ChatID:    message.ChatID,
			Type:      message.Type,
			Content:   message.Content,
			FileURL:   message.FileURL,
			FileName:  message.FileName,
			FileSize:  message.FileSize,
			CreatedAt: message.CreatedAt.Format("2006-01-02T15:04:05.000Z07:00"),
			Status:    "sent",
		}
		if message.Sender != nil {
			wsMessage.Sender = &websocket.User{
				ID:        message.Sender.ID,
				FirstName: message.Sender.FirstName,
				LastName:  message.Sender.LastName,
				FullName:  message.Sender.GetFullName(),
				Avatar:    message.Sender.Avatar,
				UserType:  message.Sender.UserType,
				Status:    message.Sender.Status,
			}
		}
		h.hub.BroadcastToChat(uint(chatID), websocket.ServerMessage{
			Type:    websocket.NewMessage,
			Message: wsMessage,
		}, userID)
	}

	c.JSON(http.StatusCreated, gin.H{
		"message": "File uploaded successfully",
		"data": gin.H{
			"message_id": message.ID,
			"file_url":   fileURL,
			"file_name":  fileName,
			"file_size":  fileSize,
			"file_type":  fileType,
		},
	})
}

// GetChatFiles 获取聊天文件列表
func (h *FileHandler) GetChatFiles(c *gin.Context) {
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

	// 获取分页参数
	limitStr := c.DefaultQuery("limit", "50")
	offsetStr := c.DefaultQuery("offset", "0")

	limit, err := strconv.Atoi(limitStr)
	if err != nil || limit <= 0 || limit > 100 {
		limit = 50
	}

	offset, err := strconv.Atoi(offsetStr)
	if err != nil || offset < 0 {
		offset = 0
	}

	// 获取文件列表
	files, err := h.fileService.GetChatFiles(uint(chatID), limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to get files"})
		return
	}

	// 转换为前端需要的格式
	fileList := make([]gin.H, 0, len(files))
	for _, file := range files {
		fileList = append(fileList, gin.H{
			"id":         file.ID,
			"file_url":   file.FileURL,
			"file_name":  file.FileName,
			"file_size":  file.FileSize,
			"type":       file.Type,
			"created_at": file.CreatedAt,
			"sender":     file.Sender,
		})
	}

	c.JSON(http.StatusOK, gin.H{
		"files":  fileList,
		"limit":  limit,
		"offset": offset,
		"count":  len(fileList),
	})
}

// DownloadFile 下载文件
func (h *FileHandler) DownloadFile(c *gin.Context) {
	_, exists := middleware.GetUserIDFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "User not authenticated"})
		return
	}

	fileIDStr := c.Param("id")
	_, err := strconv.ParseUint(fileIDStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid file ID"})
		return
	}

	// 这里应该根据文件ID获取文件信息并验证权限
	// 暂时返回错误
	c.JSON(http.StatusNotImplemented, gin.H{"error": "File download not implemented yet"})
}

// ServeFile 提供文件访问
func (h *FileHandler) ServeFile(c *gin.Context) {
	// 从URL路径中提取文件路径
	filePath := c.Param("filepath")

	// 构建完整的文件路径
	fullPath := filepath.Join(config.AppConfig.Storage.Path, filePath)

	// 检查文件是否存在
	if _, err := os.Stat(fullPath); os.IsNotExist(err) {
		c.JSON(http.StatusNotFound, gin.H{"error": "File not found"})
		return
	}

	// 提供文件下载
	c.File(fullPath)
}
