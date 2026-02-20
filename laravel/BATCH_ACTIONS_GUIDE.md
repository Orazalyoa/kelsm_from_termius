# Dcat Admin 批量操作指南

## ✅ 正确的批量操作写法

### 基本语法

```php
$grid->batchActions(function ($batch) {
    $batch->add(new class('操作名称') extends \Dcat\Admin\Grid\BatchAction {
        public function handle()
        {
            // 获取选中的 ID
            $ids = $this->getKey();
            
            // 执行操作
            YourModel::whereIn('id', $ids)->update(['field' => 'value']);
            // 或者
            YourModel::whereIn('id', $ids)->delete();
            
            // 返回响应
            return $this->response()->success('操作成功')->refresh();
        }
    });
});
```

## 📝 实际示例

### 1. 更新状态

```php
$grid->batchActions(function ($batch) {
    // 激活用户
    $batch->add(new class('激活用户') extends \Dcat\Admin\Grid\BatchAction {
        public function handle()
        {
            \App\Models\User::whereIn('id', $this->getKey())
                ->update(['status' => 'active']);
            
            return $this->response()->success('已激活选中的用户')->refresh();
        }
    });
    
    // 停用用户
    $batch->add(new class('停用用户') extends \Dcat\Admin\Grid\BatchAction {
        public function handle()
        {
            \App\Models\User::whereIn('id', $this->getKey())
                ->update(['status' => 'inactive']);
            
            return $this->response()->success('已停用选中的用户')->refresh();
        }
    });
});
```

### 2. 批量删除

```php
$grid->batchActions(function ($batch) {
    $batch->add(new class('批量删除') extends \Dcat\Admin\Grid\BatchAction {
        public function handle()
        {
            \App\Models\Message::whereIn('id', $this->getKey())->delete();
            
            return $this->response()->success('已删除选中的消息')->refresh();
        }
    });
});
```

### 3. 复杂操作（带确认对话框）

```php
$grid->batchActions(function ($batch) {
    $batch->add(new class('强制删除') extends \Dcat\Admin\Grid\BatchAction {
        public function confirm()
        {
            return '确定要删除选中的记录吗？此操作不可恢复！';
        }
        
        public function handle()
        {
            $count = \App\Models\User::whereIn('id', $this->getKey())->count();
            \App\Models\User::whereIn('id', $this->getKey())->delete();
            
            return $this->response()->success("已删除 {$count} 条记录")->refresh();
        }
    });
});
```

### 4. 带表单参数的批量操作

```php
$grid->batchActions(function ($batch) {
    $batch->add(new class('分配角色') extends \Dcat\Admin\Grid\BatchAction {
        public function form()
        {
            $this->select('role_id', '选择角色')
                ->options(\App\Models\Role::pluck('name', 'id'))
                ->required();
        }
        
        public function handle()
        {
            $roleId = $this->form['role_id'];
            
            foreach ($this->getKey() as $userId) {
                $user = \App\Models\User::find($userId);
                $user->roles()->sync([$roleId]);
            }
            
            return $this->response()->success('已分配角色')->refresh();
        }
    });
});
```

### 5. 禁用默认批量删除

```php
$grid->batchActions(function ($batch) {
    $batch->disableDelete();
});
```

## ❌ 错误写法（不要使用）

```php
// ❌ 错误：直接传递字符串和闭包
$grid->batchActions(function ($batch) {
    $batch->add('激活用户', function ($ids) {
        \App\Models\User::whereIn('id', $ids)->update(['status' => 'active']);
    });
});
```

这种写法会报错：
```
TypeError: Argument 1 passed to Dcat\Admin\Grid\Tools\BatchActions::add() 
must be an instance of Dcat\Admin\Grid\BatchAction, string given
```

## 🔄 响应方法

### success() - 成功响应
```php
return $this->response()->success('操作成功')->refresh();
```

### error() - 错误响应
```php
return $this->response()->error('操作失败：错误原因');
```

### warning() - 警告响应
```php
return $this->response()->warning('部分操作失败');
```

### refresh() - 刷新页面
```php
->refresh()
```

### redirect() - 跳转页面
```php
->redirect('/admin/users')
```

### download() - 下载文件
```php
->download('filename.xlsx')
```

## 🎯 高级功能

### 1. 获取选中的模型实例

```php
public function handle()
{
    $users = \App\Models\User::whereIn('id', $this->getKey())->get();
    
    foreach ($users as $user) {
        // 对每个用户执行操作
        $user->sendNotification();
    }
    
    return $this->response()->success('通知已发送')->refresh();
}
```

### 2. 事务处理

```php
public function handle()
{
    try {
        \DB::beginTransaction();
        
        \App\Models\User::whereIn('id', $this->getKey())
            ->update(['status' => 'active']);
            
        // 其他操作...
        
        \DB::commit();
        
        return $this->response()->success('操作成功')->refresh();
    } catch (\Exception $e) {
        \DB::rollBack();
        return $this->response()->error('操作失败：' . $e->getMessage());
    }
}
```

### 3. 带进度条的长时间操作

```php
public function handle()
{
    $ids = $this->getKey();
    $total = count($ids);
    $processed = 0;
    
    foreach ($ids as $id) {
        // 执行操作
        \App\Models\User::find($id)->process();
        
        $processed++;
        // 更新进度（如果使用队列）
    }
    
    return $this->response()->success("已处理 {$processed}/{$total} 条记录")->refresh();
}
```

## 📚 完整示例：复杂的批量操作

```php
$grid->batchActions(function ($batch) {
    // 1. 批量审核
    $batch->add(new class('批量审核') extends \Dcat\Admin\Grid\BatchAction {
        public function form()
        {
            $this->radio('action', '审核动作')
                ->options([
                    'approve' => '通过',
                    'reject' => '拒绝',
                ])
                ->required();
                
            $this->textarea('reason', '备注')->rows(3);
        }
        
        public function confirm()
        {
            return '确定要批量审核选中的记录吗？';
        }
        
        public function handle()
        {
            $action = $this->form['action'];
            $reason = $this->form['reason'] ?? '';
            
            try {
                \DB::beginTransaction();
                
                $items = \App\Models\Consultation::whereIn('id', $this->getKey())->get();
                
                foreach ($items as $item) {
                    $item->status = $action === 'approve' ? 'approved' : 'rejected';
                    $item->review_reason = $reason;
                    $item->reviewed_at = now();
                    $item->reviewed_by = admin()->user()->id;
                    $item->save();
                    
                    // 发送通知
                    $item->user->notify(new ReviewNotification($item));
                }
                
                \DB::commit();
                
                $message = $action === 'approve' ? '已通过审核' : '已拒绝';
                return $this->response()->success($message)->refresh();
                
            } catch (\Exception $e) {
                \DB::rollBack();
                return $this->response()->error('操作失败：' . $e->getMessage());
            }
        }
    });
});
```

## 🚨 注意事项

1. **必须使用 `$this->getKey()` 获取选中的 ID**
   - 不要使用 `$ids` 参数

2. **必须返回响应对象**
   - 使用 `$this->response()` 返回

3. **建议添加确认对话框**
   - 使用 `confirm()` 方法

4. **大批量操作建议使用队列**
   - 避免请求超时

5. **记得使用事务**
   - 保证数据一致性

## 🔗 相关文档

- [Dcat Admin 官方文档 - 批量操作](https://learnku.com/docs/dcat-admin/2.x/model-grid-batch-actions/8119)
- [Grid 相关功能](https://learnku.com/docs/dcat-admin/2.x/model-grid/8095)

