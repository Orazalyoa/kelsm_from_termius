<?php

return [
    'chat' => '聊天',
    'sender' => '发送者',
    'type' => '类型',
    'content' => '内容',
    'file_url' => '文件URL',
    'file_link' => '文件链接',
    'file_name' => '文件名',
    'file_size' => '文件大小',
    'file_size_bytes' => '文件大小（字节）',
    'send_time' => '发送时间',
    'created_at' => '创建时间',
    'updated_at' => '更新时间',
    'system' => '系统',
    'system_message' => '系统消息',
    'read_status' => '已读状态',
    'no_status' => '无状态记录',
    
    // Message types
    'types' => [
        'text' => '文本',
        'image' => '图片',
        'document' => '文档',
        'video' => '视频',
        'system' => '系统消息',
    ],
    
    // Statuses
    'statuses' => [
        'sent' => '已发送',
        'delivered' => '已送达',
        'read' => '已读',
        'failed' => '失败',
    ],
    
    // Actions
    'actions' => [
        'batch_delete' => '批量删除',
        'deleted_success' => '所选消息已删除',
    ],
    
    // Form help
    'help' => [
        'system_message_empty' => '系统消息可以为空',
    ],
    
    // System messages for consultation
    'lawyer_joined' => '律师 :lawyer 已加入咨询',
    'lawyer_removed' => '律师 :lawyer 已从咨询中移除',
    'status_changed_to' => '咨询状态已变更为：:status',
    'consultation_archived' => '咨询已被归档。',
    'consultation_unarchived' => '咨询已从归档中恢复。',
    
    // Status labels for system messages
    'status_pending' => '待处理',
    'status_in_progress' => '进行中',
    'status_archived' => '已归档',
    'status_cancelled' => '已取消',
];

