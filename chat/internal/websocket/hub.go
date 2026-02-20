package websocket

import (
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/middleware"
	"net/http"
	"sync"

	"github.com/gin-gonic/gin"
	"github.com/gorilla/websocket"
	"github.com/sirupsen/logrus"
	"gorm.io/gorm"
)

// Hub WebSocket 连接管理中心
type Hub struct {
	// 注册的客户端
	Clients map[*Client]bool

	// 注册客户端
	Register chan *Client

	// 注销客户端
	Unregister chan *Client

	// 广播消息到所有客户端
	Broadcast chan ServerMessage

	// 广播消息到指定聊天室
	BroadcastToChatChan chan BroadcastToChatMessage

	// 互斥锁
	Mutex sync.RWMutex
}

// BroadcastToChatMessage 广播到聊天室的消息
type BroadcastToChatMessage struct {
	ChatID  uint
	Message ServerMessage
	Exclude uint // 排除的用户ID
}

var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 1024,
	CheckOrigin: func(r *http.Request) bool {
		return true // 在生产环境中应该检查 origin
	},
}

// NewHub 创建新的 Hub
func NewHub() *Hub {
	return &Hub{
		Clients:             make(map[*Client]bool),
		Register:            make(chan *Client),
		Unregister:          make(chan *Client),
		Broadcast:           make(chan ServerMessage),
		BroadcastToChatChan: make(chan BroadcastToChatMessage),
	}
}

// Run 运行 Hub
func (h *Hub) Run() {
	for {
		select {
		case client := <-h.Register:
			h.Mutex.Lock()
			h.Clients[client] = true
			h.Mutex.Unlock()
			logrus.Infof("Client %d connected", client.ID)

		case client := <-h.Unregister:
			h.Mutex.Lock()
			if _, ok := h.Clients[client]; ok {
				delete(h.Clients, client)
				close(client.Send)
			}
			h.Mutex.Unlock()
			logrus.Infof("Client %d disconnected", client.ID)

		case message := <-h.Broadcast:
			h.Mutex.RLock()
			for client := range h.Clients {
				select {
				case client.Send <- message:
				default:
					close(client.Send)
					delete(h.Clients, client)
				}
			}
			h.Mutex.RUnlock()

		case broadcastMsg := <-h.BroadcastToChatChan:
			h.Mutex.RLock()
			for client := range h.Clients {
				if client.IsInChat(broadcastMsg.ChatID) && client.ID != broadcastMsg.Exclude {
					select {
					case client.Send <- broadcastMsg.Message:
					default:
						close(client.Send)
						delete(h.Clients, client)
					}
				}
			}
			h.Mutex.RUnlock()
		}
	}
}

// HandleWebSocket 处理 WebSocket 连接
func (h *Hub) HandleWebSocket(c *gin.Context) {
	// 使用可选认证中间件
	middleware.OptionalAuthMiddleware()(c)

	// 获取用户信息
	user, exists := middleware.GetUserFromContext(c)
	if !exists {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Authentication required"})
		return
	}

	// 升级连接
	conn, err := upgrader.Upgrade(c.Writer, c.Request, nil)
	if err != nil {
		logrus.Errorf("WebSocket upgrade error: %v", err)
		return
	}

	// 创建客户端
	client := NewClient(h, conn, user)

	// 注册客户端
	h.Register <- client

	// 启动读写协程
	go client.WritePump()
	go client.ReadPump()
}

// BroadcastToChat 广播消息到指定聊天室
func (h *Hub) BroadcastToChat(chatID uint, message ServerMessage, excludeUserID uint) {
	h.BroadcastToChatChan <- BroadcastToChatMessage{
		ChatID:  chatID,
		Message: message,
		Exclude: excludeUserID,
	}
}

// GetClientCount 获取客户端数量
func (h *Hub) GetClientCount() int {
	h.Mutex.RLock()
	defer h.Mutex.RUnlock()
	return len(h.Clients)
}

// GetClientsInChat 获取指定聊天室中的客户端
func (h *Hub) GetClientsInChat(chatID uint) []*Client {
	h.Mutex.RLock()
	defer h.Mutex.RUnlock()

	var clients []*Client
	for client := range h.Clients {
		if client.IsInChat(chatID) {
			clients = append(clients, client)
		}
	}
	return clients
}

// GetDB 获取数据库连接
func (h *Hub) GetDB() *gorm.DB {
	return database.DB
}
