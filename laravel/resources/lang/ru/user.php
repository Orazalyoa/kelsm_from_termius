<?php

return [
    'fields' => [
        'first_name' => 'Имя',
        'last_name' => 'Фамилия',
        'email' => 'Email',
        'phone' => 'Телефон',
        'avatar' => 'Аватар',
        'user_type' => 'Тип пользователя',
        'status' => 'Статус',
        'gender' => 'Пол',
        'locale' => 'Язык',
        'last_login_at' => 'Последний вход',
        'created_at' => 'Дата регистрации',
        'professions' => 'Профессии',
        'organizations' => 'Организации',
        'password' => 'Пароль',
        'country_code' => 'Код страны',
        'profession_id' => 'Профессия',
    ],
    'options' => [
        'user_types' => [
            'company_admin' => 'Администратор компании',
            'expert' => 'Эксперт',
            'lawyer' => 'Юрист',
            'operator' => 'Оператор',
        ],
        'status' => [
            'active' => 'Активен',
            'inactive' => 'Неактивен',
            'suspended' => 'Приостановлен',
        ],
        'gender' => [
            'male' => 'Мужской',
            'female' => 'Женский',
            'other' => 'Другой',
        ],
        'locales' => [
            'ru' => 'Русский',
            'kk' => 'Казахский',
        ],
    ],
    'actions' => [
        'activate' => 'Активировать пользователей',
        'deactivate' => 'Деактивировать пользователей',
        'suspend' => 'Приостановить пользователей',
        'activated_success' => 'Выбранные пользователи активированы',
        'deactivated_success' => 'Выбранные пользователи деактивированы',
        'suspended_success' => 'Выбранные пользователи приостановлены',
    ],
    'help' => [
        'user_type' => 'Только юристы могут быть созданы через админ-панель',
        'password_create' => 'Минимум 8 символов',
        'password_edit' => 'Оставьте пустым, чтобы сохранить текущий пароль',
        'profession' => 'Выберите основную профессию юриста',
    ],
    'labels' => [
        'none' => 'Нет',
    ],
];

