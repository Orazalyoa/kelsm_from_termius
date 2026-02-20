package handlers

import (
	"kelisim-chat/internal/websocket"

	"github.com/gin-gonic/gin"
)

// WebSocketHandler WebSocket 处理器
type WebSocketHandler struct {
	hub *websocket.Hub
}

// NewWebSocketHandler 创建 WebSocket 处理器
func NewWebSocketHandler(hub *websocket.Hub) *WebSocketHandler {
	return &WebSocketHandler{
		hub: hub,
	}
}

// HandleWebSocket 处理 WebSocket 连接
func (h *WebSocketHandler) HandleWebSocket(c *gin.Context) {
	h.hub.HandleWebSocket(c)
}
