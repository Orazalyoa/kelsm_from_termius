package websocket

// MessageType WebSocket 消息类型
type MessageType string

const (
	// 客户端发送的消息类型
	SendMessage MessageType = "send_message"
	JoinChat    MessageType = "join_chat"
	LeaveChat   MessageType = "leave_chat"
	Typing      MessageType = "typing"
	StopTyping  MessageType = "stop_typing"
	ReadMessage MessageType = "read_message"

	// 服务器发送的消息类型
	NewMessage        MessageType = "new_message"
	MessageAck        MessageType = "message_ack"
	MessageStatus     MessageType = "message_status"
	ParticipantJoined MessageType = "participant_joined"
	ParticipantLeft   MessageType = "participant_left"
	UserTyping        MessageType = "user_typing"
	UserStopTyping    MessageType = "user_stop_typing"
	Error             MessageType = "error"
	Success           MessageType = "success"
)

// ClientMessage 客户端发送的消息
type ClientMessage struct {
	Type        MessageType `json:"type"`
	ChatID      uint        `json:"chat_id,omitempty"`
	Content     string      `json:"content,omitempty"`
	MessageType string      `json:"message_type,omitempty"`
	TempID      string      `json:"temp_id,omitempty"`
	MessageID   uint        `json:"message_id,omitempty"`
}

// ServerMessage 服务器发送的消息
type ServerMessage struct {
	Type      MessageType `json:"type"`
	Message   *Message    `json:"message,omitempty"`
	ChatID    uint        `json:"chat_id,omitempty"`
	User      *User       `json:"user,omitempty"`
	Error     string      `json:"error,omitempty"`
	Success   string      `json:"success,omitempty"`
	TempID    string      `json:"temp_id,omitempty"`
	MessageID uint        `json:"message_id,omitempty"`
	Status    string      `json:"status,omitempty"`
}

// Message 消息结构
type Message struct {
	ID        uint    `json:"id"`
	ChatID    uint    `json:"chat_id"`
	Sender    *User   `json:"sender,omitempty"`
	Type      string  `json:"type"`
	Content   *string `json:"content,omitempty"`
	FileURL   *string `json:"file_url,omitempty"`
	FileName  *string `json:"file_name,omitempty"`
	FileSize  *int64  `json:"file_size,omitempty"`
	CreatedAt string  `json:"created_at"`
	Status    string  `json:"status,omitempty"`
}

// User 用户结构 (WebSocket 消息中的简化用户信息)
type User struct {
	ID        uint    `json:"id"`
	FirstName string  `json:"first_name"`
	LastName  string  `json:"last_name"`
	FullName  string  `json:"full_name"`
	Avatar    *string `json:"avatar,omitempty"`
	UserType  string  `json:"user_type"`
	Status    string  `json:"status"`
}
