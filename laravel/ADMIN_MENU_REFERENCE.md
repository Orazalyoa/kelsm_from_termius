# Admin 菜单快速参考

## ✅ 已自动添加的菜单

业务菜单已通过 `ProjectMenuSeeder` 自动创建，刷新后台即可看到。

## 📋 菜单列表

| 菜单名称 | URI | 图标 | 说明 |
|---------|-----|------|------|
| **Dashboard** | `/` | `feather icon-bar-chart-2` | 首页仪表板 |
| **用户管理** | - | `feather icon-users` | 父级菜单 |
| └─ App 用户 | `app-users` | - | 应用用户列表 |
| **组织管理** | `organizations` | `feather icon-briefcase` | 组织/公司管理 |
| **职业管理** | `professions` | `feather icon-award` | 职业类别管理 |
| **邀请码管理** | `invite-codes` | `feather icon-gift` | 邀请码生成与管理 |
| **聊天管理** | - | `feather icon-message-square` | 父级菜单 |
| └─ 聊天列表 | `chats` | - | 所有聊天会话 |
| └─ 消息管理 | `messages` | - | 聊天消息记录 |
| └─ 聊天参与者 | `chat-participants` | - | 聊天成员管理 |
| └─ 聊天文件 | `chat-files` | - | 聊天文件管理 |
| **咨询管理** | `consultations` | `feather icon-file-text` | 法律咨询管理 |
| **Admin** | - | `feather icon-settings` | 系统管理 |
| └─ Users | `auth/users` | - | 管理员用户 |
| └─ Roles | `auth/roles` | - | 角色管理 |
| └─ Permission | `auth/permissions` | - | 权限管理 |
| └─ Menu | `auth/menu` | - | 菜单管理 |
| └─ Extensions | `auth/extensions` | - | 扩展管理 |

## 🎯 如何手动添加/修改菜单

### 方式 1：后台界面操作（推荐）

1. 登录后台: http://localhost:8000/admin
2. 进入：**Admin -> Menu**
3. 点击 "新增" 或编辑已有菜单

### 方式 2：通过数据库

```sql
INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) 
VALUES (0, 100, '新菜单', 'feather icon-home', 'custom-uri', NOW(), NOW());
```

## 🔧 菜单字段详解

### Parent (父级菜单)
- `0` = 顶级菜单
- 其他数字 = 该菜单的 ID

### Title (标题)
- 显示在侧边栏的文字
- 支持中文和英文

### Icon (图标)
使用 Feather Icons 或 Font Awesome：
```
Feather Icons: feather icon-{name}
Font Awesome:  fa-{name}
```

常用图标：
```
feather icon-users          用户
feather icon-briefcase      工作
feather icon-file-text      文档
feather icon-message-square 消息
feather icon-settings       设置
feather icon-grid           全部
feather icon-award          奖项
feather icon-gift           礼物
```

### URI (路由地址)
- 不含 `/admin` 前缀
- 例如：`app-users` 实际访问 `/admin/app-users`
- 父级菜单可以留空

### Order (排序)
- 数字越小越靠前
- 建议间隔 10 便于插入新菜单

## 🔍 图标预览

访问：http://localhost:8000/admin/helpers/icons

可以看到所有可用图标及其类名。

## 📝 添加新菜单示例

假设你新建了一个 `ReportController`，要添加菜单：

### 1. 确保路由已注册
编辑 `routes/admin.php`：
```php
Route::resource('reports', \App\Admin\Controllers\ReportController::class);
```

### 2. 添加菜单
在后台 **Admin -> Menu** 中新增：
```
Parent:  0
Title:   报表管理
Icon:    feather icon-bar-chart
URI:     reports
Order:   50
```

### 3. 清除缓存
```bash
php artisan route:clear
php artisan cache:clear
```

### 4. 刷新浏览器
新菜单应该出现在侧边栏

## 🚨 常见问题

### Q: 点击菜单显示 404
**A:** 检查路由是否已注册：
```bash
php artisan route:list | findstr "你的URI"
```

### Q: 菜单不显示
**A:** 
1. 检查角色权限是否正确
2. 清除缓存：`php artisan cache:clear`
3. 退出重新登录

### Q: 图标不显示
**A:** 
1. 确保图标类名正确（带 `feather` 或 `fa-` 前缀）
2. 检查是否有拼写错误

### Q: 如何调整菜单顺序
**A:** 
1. 进入 **Admin -> Menu**
2. 编辑菜单，修改 Order 字段
3. 数字越小越靠前

## 🔄 重新运行 Seeder

如果需要重置所有菜单：

```bash
# 清空菜单表（危险！会删除所有菜单）
php artisan tinker --execute="DB::table('admin_menu')->truncate();"

# 重新初始化
php artisan db:seed --class=DcatAdminInitSeeder
php artisan db:seed --class=ProjectMenuSeeder
```

## 📚 更多资源

- Dcat Admin 官方文档: https://learnku.com/docs/dcat-admin/2.x
- Feather Icons: https://feathericons.com/
- Font Awesome: https://fontawesome.com/icons

