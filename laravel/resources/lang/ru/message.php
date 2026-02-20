<?php

return [
    'chat' => 'Чат',
    'sender' => 'Отправитель',
    'type' => 'Тип',
    'content' => 'Содержание',
    'file_url' => 'URL файла',
    'file_link' => 'Ссылка на файл',
    'file_name' => 'Имя файла',
    'file_size' => 'Размер файла',
    'file_size_bytes' => 'Размер файла (байты)',
    'send_time' => 'Время отправки',
    'created_at' => 'Создано',
    'updated_at' => 'Обновлено',
    'system' => 'Система',
    'system_message' => 'Системное сообщение',
    'read_status' => 'Статус прочтения',
    'no_status' => 'Нет записей о статусе',
    
    // Message types
    'types' => [
        'text' => 'Текст',
        'image' => 'Изображение',
        'document' => 'Документ',
        'video' => 'Видео',
        'system' => 'Системное сообщение',
    ],
    
    // Statuses
    'statuses' => [
        'sent' => 'Отправлено',
        'delivered' => 'Доставлено',
        'read' => 'Прочитано',
        'failed' => 'Ошибка',
    ],
    
    // Actions
    'actions' => [
        'batch_delete' => 'Массовое удаление',
        'deleted_success' => 'Выбранные сообщения удалены',
    ],
    
    // Form help
    'help' => [
        'system_message_empty' => 'Системные сообщения могут быть пустыми',
    ],
    
    // System messages for consultation
    'lawyer_joined' => 'Юрист :lawyer присоединился к консультации',
    'lawyer_removed' => 'Юрист :lawyer был удален из консультации',
    'operator_joined' => 'Оператор :operator присоединился к консультации',
    'status_changed_to' => 'Статус консультации изменен на: :status',
    'consultation_archived' => 'Консультация архивирована.',
    'consultation_unarchived' => 'Консультация восстановлена из архива.',
    
    // Status labels for system messages
    'status_pending' => 'Ожидание',
    'status_in_progress' => 'В процессе',
    'status_archived' => 'В архиве',
    'status_cancelled' => 'Отменено',
];

