# Dcat Admin 菜单添加指南

## 如何添加菜单

登录后台后，进入：**Admin -> Menu**，点击 "新增" 按钮

## 菜单字段说明

| 字段 | 说明 | 示例 |
|------|------|------|
| **Parent** | 父级菜单 | 选择 0 为顶级菜单，或选择其他菜单作为父级 |
| **Title** | 菜单标题（显示在侧边栏） | `用户管理`、`App Users` |
| **Icon** | 图标类名 | `feather icon-users`、`fa-users` |
| **URI** | 路由地址（不含 `/admin` 前缀） | `app-users`、`organizations` |
| **Roles** | 可访问的角色 | 多选，留空表示所有角色可访问 |
| **Permission** | 需要的权限 | 可选 |

## 可用的图标

Dcat Admin 支持以下图标库：

### Feather Icons (推荐)
```
feather icon-users          用户
feather icon-briefcase      职业/工作
feather icon-home           组织
feather icon-mail           邮件
feather icon-message-square 消息/聊天
feather icon-file-text      咨询/文档
feather icon-gift           邀请码
feather icon-bar-chart-2    统计/Dashboard
feather icon-settings       设置
feather icon-grid           网格/全部
```

### Font Awesome
```
fa-users                    用户
fa-briefcase                职业
fa-building                 组织
fa-envelope                 邮件
fa-comments                 聊天
fa-file-alt                 文档
fa-ticket-alt               票券/邀请码
fa-chart-bar                图表
```

## 你的项目可以添加的菜单

根据你现有的 Controllers，建议添加以下菜单：

---

### 1. 顶级菜单 - 用户管理
```
Parent:       0 (Root)
Title:        用户管理
Icon:         feather icon-users
URI:          (留空，作为分组)
```

#### 子菜单 - App 用户
```
Parent:       用户管理
Title:        App 用户
Icon:         (留空或 feather icon-user)
URI:          app-users
```

---

### 2. 顶级菜单 - 组织管理
```
Parent:       0 (Root)
Title:        组织管理
Icon:         feather icon-briefcase
URI:          organizations
```

---

### 3. 顶级菜单 - 职业管理
```
Parent:       0 (Root)
Title:        职业管理
Icon:         feather icon-award
URI:          professions
```

---

### 4. 顶级菜单 - 邀请码管理
```
Parent:       0 (Root)
Title:        邀请码管理
Icon:         feather icon-gift
URI:          invite-codes
```

---

### 5. 顶级菜单 - 聊天管理
```
Parent:       0 (Root)
Title:        聊天管理
Icon:         feather icon-message-square
URI:          (留空，作为分组)
```

#### 子菜单 - 聊天列表
```
Parent:       聊天管理
Title:        聊天列表
Icon:         (留空)
URI:          chats
```

#### 子菜单 - 消息管理
```
Parent:       聊天管理
Title:        消息管理
Icon:         (留空)
URI:          messages
```

#### 子菜单 - 聊天参与者
```
Parent:       聊天管理
Title:        聊天参与者
Icon:         (留空)
URI:          chat-participants
```

#### 子菜单 - 聊天文件
```
Parent:       聊天管理
Title:        聊天文件
Icon:         (留空)
URI:          chat-files
```

---

### 6. 顶级菜单 - 咨询管理
```
Parent:       0 (Root)
Title:        咨询管理
Icon:         feather icon-file-text
URI:          consultations
```

---

## 注意事项

1. **URI 不需要加 `/admin` 前缀**
   - ✅ 正确: `app-users`
   - ❌ 错误: `/admin/app-users`

2. **顶级菜单作为分组时 URI 留空**
   - 如果下面有子菜单，父菜单的 URI 可以留空

3. **路由必须先定义**
   - 确保 URI 对应的路由在 `routes/admin.php` 或通过 Controller 注册

4. **Order 字段控制显示顺序**
   - 数字越小越靠前

5. **图标可以留空**
   - 子菜单通常不需要图标

## 快速添加脚本（可选）

如果你想通过代码批量添加菜单，可以创建一个 Seeder：

运行命令：
```bash
php artisan db:seed --class=ProjectMenuSeeder
```


