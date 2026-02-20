# 用户模块对接文档

## 概述

Go 聊天系统已完全对接 Laravel 用户模块，支持所有用户类型和认证机制。

## 用户模型对接

### Laravel User 模型字段映射

| Laravel 字段 | Go 字段 | 类型 | 说明 |
|-------------|---------|------|------|
| `id` | `ID` | `uint` | 用户ID |
| `user_type` | `UserType` | `string` | 用户类型: company_admin, expert, lawyer |
| `email` | `Email` | `*string` | 邮箱 (可为空) |
| `phone` | `Phone` | `*string` | 手机号 (可为空) |
| `country_code` | `CountryCode` | `*string` | 国家代码 |
| `password` | `Password` | `string` | 密码 (JSON中隐藏) |
| `first_name` | `FirstName` | `string` | 名字 |
| `last_name` | `LastName` | `string` | 姓氏 |
| `gender` | `Gender` | `*string` | 性别: male, female, other |
| `avatar` | `Avatar` | `*string` | 头像路径 |
| `locale` | `Locale` | `string` | 语言设置 (默认: ru) |
| `status` | `Status` | `string` | 状态: active, inactive, suspended |
| `email_verified_at` | `EmailVerifiedAt` | `*time.Time` | 邮箱验证时间 |
| `phone_verified_at` | `PhoneVerifiedAt` | `*time.Time` | 手机验证时间 |
| `last_login_at` | `LastLoginAt` | `*time.Time` | 最后登录时间 |
| `last_login_ip` | `LastLoginIP` | `*string` | 最后登录IP |
| `created_at` | `CreatedAt` | `time.Time` | 创建时间 |
| `updated_at` | `UpdatedAt` | `time.Time` | 更新时间 |
| `deleted_at` | `DeletedAt` | `gorm.DeletedAt` | 软删除时间 |

### 用户类型支持

- **company_admin**: 公司管理员
- **expert**: 专家
- **lawyer**: 律师

### 用户状态支持

- **active**: 激活状态
- **inactive**: 未激活状态
- **suspended**: 暂停状态

## JWT 认证对接

### Laravel JWT Token 格式

```json
{
  "sub": 123,           // 用户ID
  "user_type": "expert", // 用户类型
  "iss": "kelisim",     // 签发者
  "iat": 1640995200,    // 签发时间
  "exp": 1640998800,    // 过期时间
  "nbf": 1640995200,    // 生效时间
  "jti": "unique_id"    // JWT ID
}
```

### Go 认证中间件功能

1. **Token 解析**: 支持 Laravel JWT token 格式
2. **用户验证**: 从数据库加载完整用户信息
3. **类型验证**: 验证用户类型是否匹配
4. **状态检查**: 确保用户处于激活状态
5. **上下文存储**: 将用户信息存储到请求上下文

## 用户权限控制

### 聊天权限

- 所有用户类型都可以参与聊天
- 律师不能创建咨询 (通过 `CanCreateConsultations()` 方法控制)
- 公司管理员和专家可以创建聊天室

### 文件权限

- 所有用户都可以上传和下载聊天文件
- 文件访问权限基于聊天室参与者身份

## API 端点对接

### 认证相关

- **登录**: `POST /api/auth/login` (Laravel)
- **注册**: `POST /api/auth/register` (Laravel)
- **用户信息**: `GET /api/auth/me` (Laravel)

### 聊天相关

- **聊天列表**: `GET /api/chats` (Go)
- **创建聊天**: `POST /api/chats` (Go)
- **聊天详情**: `GET /api/chats/:id` (Go)
- **发送消息**: `POST /api/chats/:id/messages` (Go)
- **文件上传**: `POST /api/chats/:id/files` (Go)

## WebSocket 用户信息

### 用户数据结构

```json
{
  "id": 123,
  "first_name": "John",
  "last_name": "Doe",
  "full_name": "John Doe",
  "avatar": "avatars/user123.jpg",
  "user_type": "expert",
  "status": "active"
}
```

### 实时事件

- **用户加入聊天**: `participant_joined`
- **用户离开聊天**: `participant_left`
- **用户正在输入**: `user_typing`
- **用户停止输入**: `user_stop_typing`

## 配置要求

### 环境变量

```env
# JWT 配置 (必须与 Laravel 一致)
JWT_SECRET=your_jwt_secret_here
JWT_ALGO=HS256

# 数据库配置 (必须与 Laravel 一致)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kelisim
DB_USERNAME=root
DB_PASSWORD=your_password

# 头像URL配置
AVATAR_BASE_URL=http://localhost:8000/storage/
```

## 测试验证

### 1. 用户认证测试

```bash
# 使用 Laravel 登录获取 token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier": "user@example.com", "password": "password"}'

# 使用 token 访问 Go 聊天 API
curl -X GET http://localhost:8080/api/chats \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 2. WebSocket 连接测试

```javascript
// 使用 Laravel JWT token 连接 WebSocket
const ws = new WebSocket('ws://localhost:8080/ws?token=YOUR_JWT_TOKEN');
```

## 注意事项

1. **JWT Secret 一致性**: Go 和 Laravel 必须使用相同的 JWT secret
2. **数据库连接**: 两个系统必须连接同一个数据库
3. **用户状态**: 只有 `active` 状态的用户才能使用聊天功能
4. **头像URL**: 需要正确配置头像文件的访问路径
5. **时区处理**: 确保两个系统使用相同的时区设置

## 故障排除

### 常见问题

1. **JWT 验证失败**: 检查 JWT secret 是否一致
2. **用户未找到**: 检查数据库连接和用户ID
3. **权限不足**: 检查用户类型和状态
4. **WebSocket 连接失败**: 检查 token 格式和认证中间件

### 调试方法

1. 启用详细日志记录
2. 检查 JWT token 内容
3. 验证数据库查询结果
4. 测试 API 端点响应
