<?php

return [
    'fields' => [
        'logo' => 'Логотип',
        'name' => 'Название',
        'company_id' => 'ID компании',
        'description' => 'Описание',
        'contact_name' => 'Контактное лицо',
        'phone' => 'Телефон',
        'email' => 'Email',
        'status' => 'Статус',
        'created_by' => 'Создал',
        'members_count' => 'Участники',
        'created_at' => 'Дата создания',
    ],
    'options' => [
        'status' => [
            'active' => 'Активна',
            'inactive' => 'Неактивна',
        ],
    ],
    'actions' => [
        'activate' => 'Активировать организации',
        'deactivate' => 'Деактивировать организации',
        'activated_success' => 'Выбранные организации активированы',
        'deactivated_success' => 'Выбранные организации деактивированы',
    ],
    'help' => [
        'company_id' => 'Уникальный идентификатор компании, например @comp-1234',
    ],
    'labels' => [
        'created_by' => 'Создал',
        'members' => 'Участники',
        'none' => 'Нет',
    ],
];

