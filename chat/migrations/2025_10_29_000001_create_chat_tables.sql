-- 创建聊天系统相关表
-- 注意：此迁移依赖 users 表，请确保先运行 Laravel backend 的迁移

-- 使用正确的数据库
USE `kelisim`;

-- 检查 users 表是否存在
-- 如果不存在，请先运行: php artisan migrate (在 kelisim-backend 项目中)

-- 1. chats 表 - 聊天室
CREATE TABLE IF NOT EXISTS `chats` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL COMMENT '聊天标题',
    `type` enum('private','group') NOT NULL DEFAULT 'group' COMMENT '聊天类型',
    `created_by` bigint(20) unsigned NOT NULL COMMENT '创建者ID',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天室表';

-- 2. chat_participants 表 - 聊天参与者
CREATE TABLE IF NOT EXISTS `chat_participants` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `chat_id` bigint(20) unsigned NOT NULL COMMENT '聊天室ID',
    `user_id` bigint(20) unsigned NOT NULL COMMENT '用户ID',
    `role` varchar(50) DEFAULT NULL COMMENT '参与者角色/职位',
    `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '加入时间',
    `last_read_at` timestamp NULL DEFAULT NULL COMMENT '最后读取时间',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_chat_user` (`chat_id`,`user_id`),
    KEY `idx_chat_id` (`chat_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_chat_participants_chat_id` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_participants_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天参与者表';

-- 3. messages 表 - 消息记录
CREATE TABLE IF NOT EXISTS `messages` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `chat_id` bigint(20) unsigned NOT NULL COMMENT '聊天室ID',
    `sender_id` bigint(20) unsigned DEFAULT NULL COMMENT '发送者ID，NULL表示系统消息',
    `type` enum('text','document','image','system') NOT NULL DEFAULT 'text' COMMENT '消息类型',
    `content` text COMMENT '文本内容',
    `file_url` varchar(500) DEFAULT NULL COMMENT '文件URL',
    `file_name` varchar(255) DEFAULT NULL COMMENT '文件名',
    `file_size` bigint(20) DEFAULT NULL COMMENT '文件大小(字节)',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_chat_id_created_at` (`chat_id`,`created_at`),
    KEY `idx_sender_id` (`sender_id`),
    KEY `idx_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_messages_chat_id` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_sender_id` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息记录表';

-- 4. message_status 表 - 消息状态（已读/未读）
CREATE TABLE IF NOT EXISTS `message_status` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `message_id` bigint(20) unsigned NOT NULL COMMENT '消息ID',
    `user_id` bigint(20) unsigned NOT NULL COMMENT '用户ID',
    `status` enum('sent','delivered','read','failed') NOT NULL DEFAULT 'sent' COMMENT '消息状态',
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_message_user` (`message_id`,`user_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_message_status_message_id` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_message_status_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息状态表';

-- 5. chat_files 表 - 聊天文件索引
CREATE TABLE IF NOT EXISTS `chat_files` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `chat_id` bigint(20) unsigned NOT NULL COMMENT '聊天室ID',
    `message_id` bigint(20) unsigned NOT NULL COMMENT '消息ID',
    `file_type` enum('document','image','video') NOT NULL COMMENT '文件类型',
    `file_url` varchar(500) NOT NULL COMMENT '文件URL',
    `file_name` varchar(255) NOT NULL COMMENT '文件名',
    `file_size` bigint(20) NOT NULL COMMENT '文件大小(字节)',
    `uploaded_by` bigint(20) unsigned NOT NULL COMMENT '上传者ID',
    `created_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_chat_id` (`chat_id`),
    KEY `idx_message_id` (`message_id`),
    KEY `idx_uploaded_by` (`uploaded_by`),
    CONSTRAINT `fk_chat_files_chat_id` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_files_message_id` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_files_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天文件索引表';
