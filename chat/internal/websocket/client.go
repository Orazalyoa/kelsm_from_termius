package websocket

import (
	"kelisim-chat/internal/models"
	"sync"
	"time"

	"github.com/gorilla/websocket"
	"github.com/sirupsen/logrus"
)

// Client WebSocket 客户端
type Client struct {
	ID               uint
	User             *models.User
	Conn             *websocket.Conn
	Send             chan ServerMessage
	Hub              *Hub
	ActiveChats      map[uint]bool // 用户当前打开的聊天室（用于UI状态，如正在输入）
	ParticipantChats map[uint]bool // 用户参与的所有聊天室（从数据库加载）
	Mutex            sync.RWMutex
}

// NewClient 创建新的客户端
func NewClient(hub *Hub, conn *websocket.Conn, user *models.User) *Client {
	client := &Client{
		ID:               user.ID,
		User:             user,
		Conn:             conn,
		Send:             make(chan ServerMessage, 256),
		Hub:              hub,
		ActiveChats:      make(map[uint]bool),
		ParticipantChats: make(map[uint]bool),
	}

	// 加载用户参与的所有聊天室
	client.loadParticipantChats()

	return client
}

// ReadPump 读取客户端消息
func (c *Client) ReadPump() {
	defer func() {
		c.Hub.Unregister <- c
		c.Conn.Close()
	}()

	// 设置读取限制
	c.Conn.SetReadLimit(512)
	c.Conn.SetReadDeadline(time.Now().Add(60 * time.Second))

	// 设置 pong 处理器
	c.Conn.SetPongHandler(func(string) error {
		c.Conn.SetReadDeadline(time.Now().Add(60 * time.Second))
		return nil
	})

	for {
		var clientMsg ClientMessage
		err := c.Conn.ReadJSON(&clientMsg)
		if err != nil {
			if websocket.IsUnexpectedCloseError(err, websocket.CloseGoingAway, websocket.CloseAbnormalClosure) {
				logrus.Errorf("WebSocket error: %v", err)
			}
			break
		}

		// 处理客户端消息
		c.handleMessage(clientMsg)
	}
}

// WritePump 向客户端发送消息
func (c *Client) WritePump() {
	defer c.Conn.Close()

	for {
		select {
		case message, ok := <-c.Send:
			c.Conn.SetWriteDeadline(time.Now().Add(10 * time.Second))
			if !ok {
				c.Conn.WriteMessage(websocket.CloseMessage, []byte{})
				return
			}

			if err := c.Conn.WriteJSON(message); err != nil {
				logrus.Errorf("Error writing message: %v", err)
				return
			}
		}
	}
}

// handleMessage 处理客户端消息
func (c *Client) handleMessage(msg ClientMessage) {
	switch msg.Type {
	case SendMessage:
		c.handleSendMessage(msg)
	case JoinChat:
		c.handleJoinChat(msg)
	case LeaveChat:
		c.handleLeaveChat(msg)
	case Typing:
		c.handleTyping(msg)
	case StopTyping:
		c.handleStopTyping(msg)
	case ReadMessage:
		c.handleReadMessage(msg)
	default:
		c.sendError("Unknown message type")
	}
}

// handleSendMessage 处理发送消息
func (c *Client) handleSendMessage(msg ClientMessage) {
	// 这里应该调用消息服务来处理消息发送
	// 暂时发送确认消息
	response := ServerMessage{
		Type:   MessageAck,
		TempID: msg.TempID,
		ChatID: msg.ChatID,
	}
	c.Send <- response
}

// handleJoinChat 处理加入聊天室（标记为活跃聊天室）
func (c *Client) handleJoinChat(msg ClientMessage) {
	c.Mutex.Lock()
	c.ActiveChats[msg.ChatID] = true
	c.Mutex.Unlock()

	logrus.Infof("User %d joined chat %d (active)", c.ID, msg.ChatID)

	// 可选：通知其他用户（用于显示"在线"状态等）
	// 暂时注释掉，减少不必要的广播
	/*
		c.Hub.BroadcastToChat(msg.ChatID, ServerMessage{
			Type:   ParticipantJoined,
			ChatID: msg.ChatID,
			User: &User{
				ID:        c.User.ID,
				FirstName: c.User.FirstName,
				LastName:  c.User.LastName,
				FullName:  c.User.GetFullName(),
				Avatar:    c.User.Avatar,
				UserType:  c.User.UserType,
				Status:    c.User.Status,
			},
		}, c.ID)
	*/
}

// handleLeaveChat 处理离开聊天室（取消活跃标记）
func (c *Client) handleLeaveChat(msg ClientMessage) {
	c.Mutex.Lock()
	delete(c.ActiveChats, msg.ChatID)
	c.Mutex.Unlock()

	logrus.Infof("User %d left chat %d (inactive)", c.ID, msg.ChatID)

	// 可选：通知其他用户
	/*
		c.Hub.BroadcastToChat(msg.ChatID, ServerMessage{
			Type:   ParticipantLeft,
			ChatID: msg.ChatID,
			User: &User{
				ID:        c.User.ID,
				FirstName: c.User.FirstName,
				LastName:  c.User.LastName,
				FullName:  c.User.GetFullName(),
				Avatar:    c.User.Avatar,
				UserType:  c.User.UserType,
				Status:    c.User.Status,
			},
		}, c.ID)
	*/
}

// handleTyping 处理正在输入
func (c *Client) handleTyping(msg ClientMessage) {
	c.Hub.BroadcastToChat(msg.ChatID, ServerMessage{
		Type:   UserTyping,
		ChatID: msg.ChatID,
		User: &User{
			ID:        c.User.ID,
			FirstName: c.User.FirstName,
			LastName:  c.User.LastName,
			FullName:  c.User.GetFullName(),
			UserType:  c.User.UserType,
		},
	}, c.ID)
}

// handleStopTyping 处理停止输入
func (c *Client) handleStopTyping(msg ClientMessage) {
	c.Hub.BroadcastToChat(msg.ChatID, ServerMessage{
		Type:   UserStopTyping,
		ChatID: msg.ChatID,
		User: &User{
			ID:        c.User.ID,
			FirstName: c.User.FirstName,
			LastName:  c.User.LastName,
			FullName:  c.User.GetFullName(),
			UserType:  c.User.UserType,
		},
	}, c.ID)
}

// handleReadMessage 处理已读消息
func (c *Client) handleReadMessage(msg ClientMessage) {
	// 这里应该更新消息状态
	// 暂时发送确认
	response := ServerMessage{
		Type:      MessageStatus,
		MessageID: msg.MessageID,
		Status:    "read",
	}
	c.Send <- response
}

// sendError 发送错误消息
func (c *Client) sendError(message string) {
	c.Send <- ServerMessage{
		Type:  Error,
		Error: message,
	}
}

// sendSuccess 发送成功消息
func (c *Client) sendSuccess(message string) {
	c.Send <- ServerMessage{
		Type:    Success,
		Success: message,
	}
}

// IsInChat 检查是否在指定聊天室中（已废弃，使用 IsParticipantOfChat）
func (c *Client) IsInChat(chatID uint) bool {
	return c.IsParticipantOfChat(chatID)
}

// IsParticipantOfChat 检查用户是否是聊天室的参与者
func (c *Client) IsParticipantOfChat(chatID uint) bool {
	c.Mutex.RLock()
	defer c.Mutex.RUnlock()
	return c.ParticipantChats[chatID]
}

// IsActiveChatOpen 检查用户是否打开了该聊天室
func (c *Client) IsActiveChatOpen(chatID uint) bool {
	c.Mutex.RLock()
	defer c.Mutex.RUnlock()
	return c.ActiveChats[chatID]
}

// loadParticipantChats 从数据库加载用户参与的所有聊天室
func (c *Client) loadParticipantChats() {
	// 导入必要的包
	db := c.Hub.GetDB()
	if db == nil {
		logrus.Error("Database connection not available in Hub")
		return
	}

	// 查询用户参与的所有聊天室ID
	var chatIDs []uint
	err := db.Table("chat_participants").
		Select("chat_id").
		Where("user_id = ?", c.ID).
		Pluck("chat_id", &chatIDs).Error

	if err != nil {
		logrus.Errorf("Failed to load participant chats for user %d: %v", c.ID, err)
		return
	}

	// 更新 ParticipantChats
	c.Mutex.Lock()
	for _, chatID := range chatIDs {
		c.ParticipantChats[chatID] = true
	}
	c.Mutex.Unlock()

	logrus.Infof("User %d loaded %d participant chats", c.ID, len(chatIDs))
}
