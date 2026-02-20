<?php

return [
    'title' => 'Название',
    'description' => 'Описание',
    'topic_type' => 'Тип темы',
    'status' => 'Статус',
    'priority' => 'Приоритет',
    'creator' => 'Создатель',
    'creator_email' => 'Email создателя',
    'assigned_lawyer' => 'Назначенный юрист',
    'assigned_lawyers' => 'Назначенные юристы',
    'assigned_operators' => 'Назначенные операторы',
    'primary_lawyer' => 'Основной юрист',
    'all_assigned_lawyers' => 'Все назначенные юристы',
    'all_assigned_operators' => 'Все назначенные операторы',
    'lawyer_email' => 'Email юриста',
    'files_count' => 'Файлы',
    'chat_room' => 'Чат',
    'created_at' => 'Создано',
    'assigned_at' => 'Назначено',
    'completed_at' => 'Завершено',
    'unassigned' => 'Не назначено',
    'no_operators' => 'Операторы не назначены',
    'not_created' => 'Не создано',
    'primary_label' => 'Основной ответственный',
    'assigned_time' => 'Время назначения',
    
    // Topic types
    'topic_types' => [
        'legal_consultation' => 'Юридическая консультация',
        'contracts_deals' => 'Контракты/Сделки',
        'legal_services' => 'Юридические услуги',
        'other' => 'Другое',
    ],
    
    // Statuses
    'statuses' => [
        'pending' => 'Ожидание',
        'in_progress' => 'В процессе',
        'archived' => 'В архиве',
        'cancelled' => 'Отменено',
    ],
    
    // Priorities
    'priorities' => [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
        'urgent' => 'Срочно',
    ],
    
    // Actions
    'actions' => [
        'assign_lawyer' => 'Назначить юриста',
        'manage_lawyers' => 'Управление юристами',
        'manage_operators' => 'Управление операторами',
        'complete_consultation' => 'Завершить консультацию',
        'view_files' => 'Файлы',
        'view_chat' => 'Просмотр чата',
        'view_chat_room' => 'Открыть чат',
    ],
    
    // Messages
    'messages' => [
        'enter_lawyer_id' => 'Выберите юриста для назначения',
        'please_enter_lawyer_id' => 'Пожалуйста, выберите юриста',
        'please_select_lawyers' => 'Пожалуйста, выберите хотя бы одного юриста',
        'please_select_operators' => 'Пожалуйста, выберите хотя бы одного оператора',
        'assign_success' => 'Юрист(ы) успешно назначены',
        'assign_operator_success' => 'Оператор(ы) успешно назначены',
        'assign_failed' => 'Ошибка назначения',
        'lawyer_invalid' => 'Выбранный пользователь не является юристом',
        'complete_success' => 'Консультация завершена',
        'invalid_status_for_complete' => 'Можно завершить только консультации в статусе "Ожидание завершения"',
    ],
    
    // Admin fields
    'admin_notes' => 'Заметки администратора',
    'admin_notes_placeholder' => 'Введите заметки администратора (необязательно)',
    
    // Status log
    'status_log' => [
        'title' => 'История статусов',
        'old_status' => 'Старый статус',
        'new_status' => 'Новый статус',
        'changed_by' => 'Изменено',
        'reason' => 'Причина',
        'new_record' => 'Новая запись',
    ],
    
    // File
    'file' => [
        'title' => 'Файлы',
        'name' => 'Имя файла',
        'type' => 'Тип',
        'size' => 'Размер',
        'version' => 'Версия',
        'latest' => 'Последняя',
        'uploader' => 'Загружено',
        'upload_time' => 'Время загрузки',
        'download' => 'Скачать',
    ],
    
    // Form help text
    'help' => [
        'select_lawyer' => 'Выбор юриста автоматически создаст чат',
        'select_multiple_lawyers' => 'Вы можете выбрать несколько юристов. Первый юрист станет основным ответственным при первом назначении. Основной юрист показан жирным шрифтом.',
        'select_multiple_operators' => 'Вы можете выбрать нескольких операторов для консультации.',
        'admin_notes' => 'Заметки администратора будут записаны в журнал статусов (необязательно)',
    ],
    
    // Updated time
    'updated_at' => 'Обновлено',
    'time' => 'Время',
];

