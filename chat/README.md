# Kelisim Chat System

基于 Go + Gin + WebSocket 的实时聊天系统，与 Laravel 后端集成。

## 技术栈

- **Go 1.21+** - 主要编程语言
- **Gin** - Web 框架
- **Gorilla WebSocket** - WebSocket 支持
- **GORM** - ORM 框架
- **MySQL** - 数据库（与 Laravel 共享）
- **JWT** - 身份认证（与 Laravel 共享）

## 功能特性

- 实时消息推送
- 多用户聊天室
- 文件上传/下载
- 消息状态管理（已读/未读）
- 参与者管理
- 与 Laravel 后端无缝集成

## 项目结构

```
kelisim-chat/
├── cmd/
│   └── server/
│       └── main.go                 # 服务入口
├── internal/
│   ├── config/
│   │   └── config.go              # 配置管理
│   ├── database/
│   │   └── mysql.go               # MySQL 连接
│   ├── middleware/
│   │   ├── auth.go                # JWT 验证中间件
│   │   └── cors.go                # CORS 中间件（允许所有来源）
│   ├── models/
│   │   ├── chat.go                # Chat model
│   │   ├── message.go             # Message model
│   │   ├── participant.go         # Participant model
│   │   ├── user.go                # User model
│   │   └── chat_file.go           # ChatFile model
│   ├── handlers/
│   │   ├── chat.go                # 聊天 REST API
│   │   ├── message.go             # 消息 REST API
│   │   ├── file.go                # 文件上传/下载
│   │   └── websocket.go           # WebSocket 连接处理
│   ├── services/
│   │   ├── chat_service.go        # 聊天业务逻辑
│   │   ├── message_service.go     # 消息业务逻辑
│   │   └── file_service.go        # 文件存储服务
│   ├── websocket/
│   │   ├── hub.go                 # WebSocket 连接管理中心
│   │   ├── client.go              # WebSocket 客户端
│   │   └── message.go             # WebSocket 消息类型
│   └── router/
│       └── router.go              # 路由配置
├── migrations/
│   └── 2025_10_29_000001_create_chat_tables.sql
├── go.mod
├── go.sum
├── env.example
└── README.md
```

## 安装和运行

### 1. 环境要求

- Go 1.21+
- MySQL 5.7+
- 与 Laravel 后端共享数据库

### 2. 安装依赖

```bash
go mod tidy
```

### 3. 配置环境变量

复制 `env.example` 为 `.env` 并配置：

```bash
cp env.example .env
```

编辑 `.env` 文件：

```env
# Server
PORT=8080
GIN_MODE=debug

# MySQL (与 Laravel 共享)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=kelisim
DB_USERNAME=root
DB_PASSWORD=your_password

# JWT (与 Laravel 共享)
JWT_SECRET=your_jwt_secret_from_laravel
JWT_ALGO=HS256

# File Storage (本地存储)
STORAGE_PATH=./storage/chat-files
STORAGE_BASE_URL=http://localhost:8080/storage/chat-files
MAX_FILE_SIZE=10485760
```

### 4. 运行数据库迁移

```bash
go run cmd/server/main.go -migrate
```

### 5. 启动服务

```bash
go run cmd/server/main.go
```

服务将在 `http://localhost:8080` 启动。

## API 文档

### 认证

所有 API 请求都需要在 Header 中携带 JWT token：

```
Authorization: Bearer <your_jwt_token>
```

### 聊天管理

- `GET /api/chats` - 获取用户的聊天列表
- `POST /api/chats` - 创建新聊天
- `GET /api/chats/:id` - 获取聊天详情
- `GET /api/chats/:id/participants` - 获取参与者列表
- `POST /api/chats/:id/participants` - 添加参与者
- `DELETE /api/chats/:id/participants/:userId` - 移除参与者

### 消息管理

- `GET /api/chats/:id/messages` - 获取聊天消息
- `POST /api/chats/:id/messages` - 发送消息
- `PUT /api/messages/:id/status` - 标记消息为已读
- `DELETE /api/messages/:id` - 删除消息
- `PUT /api/chats/:id/read` - 标记整个聊天为已读
- `GET /api/unread-count` - 获取未读消息数量

### 文件管理

- `POST /api/chats/:id/files` - 上传文件
- `GET /api/chats/:id/files` - 获取聊天文件列表
- `GET /api/files/:id/download` - 下载文件

### WebSocket

- `GET /ws?token=<jwt_token>` - WebSocket 连接

## WebSocket 消息协议

### 客户端发送

```json
{
  "type": "send_message",
  "chat_id": 123,
  "content": "消息内容",
  "message_type": "text",
  "temp_id": "client-generated-uuid"
}
```

### 服务器发送

```json
{
  "type": "new_message",
  "message": {
    "id": 456,
    "chat_id": 123,
    "sender": { "id": 1, "name": "张三" },
    "type": "text",
    "content": "消息内容",
    "created_at": "2025-10-29T10:30:00Z"
  }
}
```

## 部署

### 开发环境

1. 确保 MySQL 服务运行
2. 确保 Laravel 后端运行
3. 运行 Go 聊天服务

### 生产环境

1. 使用 systemd 或 Docker 部署
2. 配置 Nginx 反向代理
3. 设置 SSL 证书
4. 配置日志轮转

## 注意事项

1. 确保与 Laravel 后端共享相同的 JWT secret
2. 确保数据库用户有足够的权限
3. 文件存储目录需要有写权限
4. 生产环境建议使用对象存储服务

## 故障排除

### 常见问题

1. **JWT 验证失败** - 检查 JWT_SECRET 是否与 Laravel 一致
2. **数据库连接失败** - 检查数据库配置和权限
3. **文件上传失败** - 检查存储目录权限
4. **WebSocket 连接失败** - 检查网络和防火墙配置

### 日志

服务使用 logrus 记录日志，可以通过环境变量 `GIN_MODE` 控制日志级别。
