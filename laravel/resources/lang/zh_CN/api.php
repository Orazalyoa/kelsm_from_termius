<?php

return [
    // 认证
    'auth' => [
        'lawyers_admin_only' => '律师只能通过管理员面板创建',
        'invalid_invite_code' => '无效的邀请码',
        'registration_failed' => '注册失败',
        'invalid_credentials' => '无效的凭证',
        'logged_out' => '您已成功退出登录',
        'invalid_expired_invite' => '无效或已过期的邀请码',
        'password_changed' => '密码修改成功',
        'current_password_incorrect' => '当前密码不正确',
        'otp_sent_failed' => '发送 OTP 失败',
        'otp_invalid_expired' => '无效或已过期的 OTP 代码',
        'otp_verified' => 'OTP 验证成功',
        'password_reset' => '密码重置成功',
        'user_not_found' => '用户不存在',
    ],

    // 咨询
    'consultation' => [
        'created' => '咨询创建成功',
        'updated' => '咨询更新成功',
        'status_updated' => '状态更新成功',
        'unauthorized' => '未授权访问',
        'file_uploaded' => '文件上传成功',
        'file_deleted' => '文件删除成功',
        'file_not_found' => '文件未找到',
        'forbidden' => '禁止访问：您无权访问此咨询',
        'authentication_required' => '未授权：需要身份验证',
        'withdrawn' => '咨询撤回成功',
        'priority_escalated' => '优先级提升成功',
        'archived' => '咨询归档成功',
        'unarchived' => '咨询恢复成功',
    ],

    // 组织
    'organization' => [
        'created' => '组织创建成功',
        'creation_failed' => '组织创建失败',
        'updated' => '组织更新成功',
        'update_failed' => '组织更新失败',
        'forbidden' => '禁止访问',
        'member_updated' => '成员角色更新成功',
        'member_update_failed' => '成员更新失败',
        'member_removed' => '成员移除成功',
        'member_removal_failed' => '成员移除失败',
        'member_added' => '成员添加成功',
        'member_addition_failed' => '成员添加失败',
        'member_already_exists' => '用户已经是该组织的成员',
        'only_owner_delete' => '只有组织所有者可以删除组织',
        'cannot_delete_active' => '无法删除有活跃咨询的组织。请先完成或取消它们。',
        'deleted' => '组织删除成功',
        'deletion_failed' => '组织删除失败',
    ],

    // 邀请码
    'invite_code' => [
        'generated' => '邀请码生成成功',
        'generation_failed' => '邀请码生成失败',
        'deleted' => '邀请码删除成功',
        'deletion_failed' => '邀请码删除失败',
        'forbidden' => '禁止访问',
        'batch_created' => '成功创建 :count 个邀请码',
        'batch_creation_failed' => '批量创建失败',
        'organization_id_required' => '需要 organization_id',
        'invalid_expired' => '无效或已过期的邀请码',
    ],

    // 用户
    'user' => [
        'profile_updated' => '个人资料更新成功',
        'profile_update_failed' => '个人资料更新失败',
        'avatar_uploaded' => '头像上传成功',
        'invalid_password' => '密码错误',
        'account_deleted' => '账户删除成功',
        'account_deletion_failed' => '账户删除失败',
        'notification_settings_updated' => '通知设置更新成功',
        'privacy_settings_updated' => '隐私设置更新成功',
    ],

    // 通用
    'common' => [
        'success' => '操作成功完成',
        'failed' => '操作失败',
        'forbidden' => '禁止访问',
        'unauthorized' => '未授权',
        'not_found' => '资源未找到',
        'validation_error' => '验证错误',
        'server_error' => '服务器错误',
    ],
];

