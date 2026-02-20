# 多律师分配功能实现文档

## 概述
将咨询系统从单律师分配改造为支持多律师分配，允许管理员在咨询的任何阶段（除已完成和已取消）添加、修改或移除律师。

## 数据库更改

### 1. 新建中间表 `consultation_lawyers`
**文件**: `database/migrations/2025_11_13_000001_create_consultation_lawyers_table.php`

```sql
CREATE TABLE consultation_lawyers (
    id BIGINT UNSIGNED PRIMARY KEY,
    consultation_id BIGINT UNSIGNED,
    lawyer_id BIGINT UNSIGNED,
    is_primary BOOLEAN DEFAULT FALSE COMMENT '是否为主要负责律师',
    assigned_by BIGINT UNSIGNED COMMENT '分配人ID',
    assigned_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (consultation_id, lawyer_id)
);
```

**特性**:
- 多对多关系表
- `is_primary` 标记主要负责律师
- `assigned_by` 记录分配操作人
- 唯一约束防止重复分配

## 模型更改

### 1. 新建模型 `ConsultationLawyer`
**文件**: `app/Models/ConsultationLawyer.php`

中间表模型，管理咨询和律师的关联关系。

### 2. 扩展 `Consultation` 模型
**文件**: `app/Models/Consultation.php`

**新增关系**:
- `lawyers()` - 获取所有分配的律师（多对多）
- `primaryLawyer()` - 获取主要负责律师

**修改方法**:
- `scopeForLawyer()` - 扩展查询范围，包含多对多关系中的律师
- `canBeAccessedBy()` - 检查用户是否在分配的律师列表中

## Service层更改

### `ConsultationService`
**文件**: `app/Services/ConsultationService.php`

**新增方法**:
1. `assignLawyers(Consultation $consultation, array $lawyerIds, User $assignedBy, bool $setPrimary = true)`
   - 批量分配多个律师
   - 自动创建或使用现有聊天室
   - 将新律师加入聊天室
   - 发送通知给所有相关方
   - 记录状态变更日志

2. `removeLawyer(Consultation $consultation, int $lawyerId, User $removedBy)`
   - 从咨询中移除指定律师
   - 从聊天室中移除
   - 自动重新分配主要负责律师（如果移除的是主要律师）
   - 至少保留一个律师

**修改方法**:
- `assignLawyer()` - 重构为调用 `assignLawyers()` 的包装方法，保持向后兼容

## Admin界面更改

### 1. 分配表单 `AssignLawyerForm`
**文件**: `app/Admin/Forms/AssignLawyerForm.php`

**更改**:
- 从单选 `select` 改为多选 `multipleSelect`
- 显示已分配的律师作为默认值
- 支持中途添加更多律师

### 2. 行动作 `AssignLawyer`
**文件**: `app/Admin/Actions/Grid/AssignLawyer.php`

**更改**:
- 按钮文本从"分配律师"改为"管理律师"
- 在所有非完成状态下显示（pending, assigned, in_progress, delivered, awaiting_completion）
- 只在completed和cancelled状态下隐藏

### 3. 控制器 `ConsultationController`
**文件**: `app/Admin/Controllers/ConsultationController.php`

**Grid视图更改**:
- 列名从 `assignedLawyer.full_name` 改为 `lawyers`
- 显示所有分配的律师，主要负责律师以**粗体**显示
- 多个律师用逗号分隔

**Detail视图更改**:
- 添加 `primary_lawyer` 字段
- 添加 `all_assigned_lawyers` 字段，列表展示所有律师及其分配信息
- 显示每个律师的分配时间和是否为主要负责

## 翻译文件更新

所有4种语言的翻译文件已更新：
- `resources/lang/zh_CN/consultation.php` (中文)
- `resources/lang/en/consultation.php` (英文)
- `resources/lang/ru/consultation.php` (俄语)
- `resources/lang/kk/consultation.php` (哈萨克语)

**新增键**:
- `assigned_lawyers` - 分配律师（复数）
- `primary_lawyer` - 主要负责律师
- `all_assigned_lawyers` - 所有分配的律师
- `actions.manage_lawyers` - 管理律师
- `messages.please_select_lawyers` - 请选择至少一个律师
- `help.select_multiple_lawyers` - 多选律师帮助文本

## 使用指南

### 管理员操作流程

1. **首次分配律师**
   - 在待处理状态下点击"分配律师"
   - 可以选择一个或多个律师
   - 第一个律师将成为主要负责人
   - 自动创建聊天室并添加所有律师

2. **添加更多律师**
   - 在已分配/进行中状态下点击"管理律师"
   - 选择框会显示已分配的律师
   - 可以添加新的律师到列表
   - 新律师会自动加入现有聊天室

3. **修改律师分配**
   - 打开管理律师对话框
   - 重新选择律师列表
   - 保存后更新分配关系

### 数据迁移

需要运行以下命令来创建新表：

```bash
php artisan migrate
```

### 向后兼容性

- 保留 `assigned_lawyer_id` 字段，存储主要负责律师
- 旧的 `assignLawyer()` 方法仍然可用
- 现有数据无需迁移，新分配自动使用新系统

## 业务逻辑

### 主要负责律师规则
- 首次分配时，第一个律师成为主要负责律师
- 主要负责律师被移除时，自动将第一个剩余律师设为主要负责
- 咨询详情中主要负责律师以粗体显示

### 律师管理限制
- 至少需要保留一个律师
- 已完成或已取消的咨询不能修改律师分配
- 只有律师角色的用户才能被分配

### 聊天室管理
- 首次分配时创建聊天室
- 添加律师时自动加入聊天室
- 移除律师时从聊天室中移除
- 发送系统消息通知律师变更

### 通知机制
- 新分配的律师收到分配通知
- 客户收到律师分配变更通知
- 状态变更记录在日志中

## 测试建议

1. **单律师分配测试**
   - 验证单个律师分配功能正常
   - 确认向后兼容性

2. **多律师分配测试**
   - 同时分配2-5个律师
   - 验证主要负责律师标记
   - 确认所有律师都加入聊天室

3. **中途添加律师测试**
   - 在已分配状态添加律师
   - 在进行中状态添加律师
   - 验证新律师加入聊天室

4. **移除律师测试**
   - 移除非主要律师
   - 移除主要律师（验证自动重新分配）
   - 尝试移除最后一个律师（应失败）

5. **边界条件测试**
   - 重复分配同一律师（应忽略）
   - 分配非律师用户（应失败）
   - 在已完成状态尝试修改（应隐藏按钮）

## 技术细节

### 事务处理
所有数据库操作都包装在事务中，确保数据一致性。

### 性能优化
- 使用 `with()` 预加载关联关系
- 批量操作减少数据库查询

### 安全性
- 验证律师角色
- 验证咨询状态
- 记录操作人信息

## 未来扩展建议

1. **律师工作量统计**
   - 基于分配记录统计每个律师的工作量
   - 智能推荐负载较低的律师

2. **律师权限细分**
   - 主要律师和协助律师权限区分
   - 限制协助律师的某些操作

3. **批量分配**
   - 支持批量为多个咨询分配相同的律师团队

4. **分配历史**
   - 查看律师分配的完整历史记录
   - 分析律师更换原因

