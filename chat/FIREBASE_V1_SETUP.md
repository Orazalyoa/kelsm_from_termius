# Firebase Cloud Messaging V1 API 配置指南

## 概述

由于 Firebase Legacy API 已被弃用，我们已升级到 **FCM V1 API**，这是 Google 推荐的现代化推送通知方案。

## 获取 Service Account JSON

### 步骤 1：访问 Firebase Console

1. 打开：https://console.firebase.google.com/
2. 选择您的项目：**kelsim-66973**

### 步骤 2：进入 Service Accounts

1. 点击左上角的 **⚙️ 齿轮图标**
2. 选择 **项目设置 (Project settings)**
3. 切换到 **Service accounts** 标签页

### 步骤 3：生成私钥

1. 在 "Firebase Admin SDK" 部分，选择语言为 **Node.js** 或 **Go**（都可以）
2. 点击 **"Generate new private key"（生成新的私钥）** 按钮
3. 在弹出的确认对话框中，点击 **"Generate key"（生成密钥）**
4. JSON 文件将自动下载到您的电脑

### 步骤 4：保存 JSON 文件

1. 将下载的 JSON 文件重命名为：`firebase-service-account.json`
2. 移动到安全位置，例如：
   ```
   E:\Codes\2025\kelisim\kelisim-chat\firebase-service-account.json
   ```
3. **重要：** 确保此文件不会被提交到 Git 仓库
   - 检查 `.gitignore` 是否包含：`firebase-service-account.json`

## 配置后端

### Go 聊天后端

编辑 `kelisim-chat/.env` 文件：

```env
# 使用 V1 API (推荐)
FCM_SERVICE_ACCOUNT_PATH=./firebase-service-account.json

# 或使用绝对路径
# FCM_SERVICE_ACCOUNT_PATH=E:/Codes/2025/kelisim/kelisim-chat/firebase-service-account.json
```

### 重启服务

```bash
cd E:\Codes\2025\kelisim\kelisim-chat
go run cmd/server/main.go
```

查看日志，应该看到：
```
[INFO] FCM Service initialized successfully
```

## JSON 文件内容示例

下载的 JSON 文件应该包含以下字段：

```json
{
  "type": "service_account",
  "project_id": "kelsim-66973",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk-xxxxx@kelsim-66973.iam.gserviceaccount.com",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/..."
}
```

## 代码变更说明

### 新增文件

- `internal/services/fcm_service.go` - FCM V1 API 服务
- `FIREBASE_V1_SETUP.md` - 本配置指南

### 修改文件

- `internal/config/config.go` - 添加 `FCMServiceAccountPath` 配置
- `internal/handlers/message.go` - 优先使用 V1 API
- `cmd/server/main.go` - 初始化 FCM 服务
- `env.example` - 更新配置说明

## V1 API vs Legacy API 对比

| 特性 | V1 API | Legacy API |
|------|--------|------------|
| **推荐度** | ✅ 推荐 | ⚠️ 已弃用 |
| **安全性** | 更高（OAuth 2.0） | 较低（静态密钥） |
| **配置** | Service Account JSON | Server Key |
| **功能** | 完整功能 | 基础功能 |
| **支持** | 长期支持 | 将停止支持 |

## 自动降级机制

代码已实现自动降级：

1. 优先尝试使用 V1 API（如果配置了 `FCM_SERVICE_ACCOUNT_PATH`）
2. 如果 V1 API 不可用，降级到 Legacy API（如果配置了 `FCM_SERVER_KEY`）
3. 如果都没配置，推送通知功能将被禁用（应用其他功能正常）

## 测试

### 1. 检查 FCM 服务初始化

启动 Go 服务器，查看日志：

```bash
go run cmd/server/main.go
```

成功的日志：
```
[INFO] FCM Service initialized successfully
```

### 2. 测试发送通知

发送一条聊天消息，检查：
- 移动设备是否收到推送通知
- 服务器日志是否显示 "Successfully sent message: ..."

## 故障排查

### 问题 1：找不到 Service Account 文件

```
error initializing Firebase app: open ./firebase-service-account.json: no such file or directory
```

**解决方案：**
- 检查文件路径是否正确
- 使用绝对路径：`FCM_SERVICE_ACCOUNT_PATH=E:/Codes/2025/kelisim/kelisim-chat/firebase-service-account.json`

### 问题 2：权限错误

```
error getting Messaging client: permission denied
```

**解决方案：**
- 确保 Service Account 有正确的权限
- 重新生成 JSON 文件

### 问题 3：V1 API 未初始化

```
[WARN] FCM service not initialized
```

**这是正常的，如果：**
- 未配置 `FCM_SERVICE_ACCOUNT_PATH`
- 系统会自动降级到 Legacy API

## Laravel 后端配置

Laravel 后端目前仍使用 Legacy API。如需升级到 V1 API：

### 安装包

```bash
composer require kreait/firebase-php
```

### 配置

编辑 `kelisim-backend/.env`：

```env
FIREBASE_CREDENTIALS=/path/to/firebase-service-account.json
```

### 更新代码

需要修改 `app/Services/NotificationService.php` 使用 Firebase PHP SDK。

## 安全建议

⚠️ **重要安全提示：**

1. **永远不要**将 `firebase-service-account.json` 提交到 Git
2. **永远不要**在公共场所分享此文件
3. 定期轮换 Service Account 密钥
4. 为不同环境（开发/生产）使用不同的 Service Account
5. 限制 Service Account 的权限范围

## 参考资料

- [Firebase Cloud Messaging 文档](https://firebase.google.com/docs/cloud-messaging)
- [迁移到 V1 API 指南](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
- [Firebase Admin Go SDK](https://firebase.google.com/docs/admin/setup#go)

---

**配置完成后，您的推送通知系统将使用最新的 V1 API，享受更高的安全性和完整的功能支持！** 🚀

