<?php

return [
    'fields' => [
        'first_name' => 'Аты',
        'last_name' => 'Тегі',
        'email' => 'Email',
        'phone' => 'Телефон',
        'avatar' => 'Аватар',
        'user_type' => 'Пайдаланушы түрі',
        'status' => 'Күйі',
        'gender' => 'Жынысы',
        'locale' => 'Тіл',
        'last_login_at' => 'Соңғы кіру',
        'created_at' => 'Тіркелген күні',
        'professions' => 'Мамандықтар',
        'organizations' => 'Ұйымдар',
        'password' => 'Құпия сөз',
        'country_code' => 'Елдің коды',
        'profession_id' => 'Мамандық',
    ],
    'options' => [
        'user_types' => [
            'company_admin' => 'Компания әкімшісі',
            'expert' => 'Маман',
            'lawyer' => 'Заңгер',
            'operator' => 'Оператор',
        ],
        'status' => [
            'active' => 'Белсенді',
            'inactive' => 'Белсенді емес',
            'suspended' => 'Тоқтатылған',
        ],
        'gender' => [
            'male' => 'Ер',
            'female' => 'Әйел',
            'other' => 'Басқа',
        ],
        'locales' => [
            'ru' => 'Орыс',
            'kk' => 'Қазақ',
        ],
    ],
    'actions' => [
        'activate' => 'Пайдаланушыларды белсендіру',
        'deactivate' => 'Пайдаланушыларды өшіру',
        'suspend' => 'Пайдаланушыларды тоқтату',
        'activated_success' => 'Таңдалған пайдаланушылар белсендірілді',
        'deactivated_success' => 'Таңдалған пайдаланушылар өшірілді',
        'suspended_success' => 'Таңдалған пайдаланушылар тоқтатылды',
    ],
    'help' => [
        'user_type' => 'Әкімші панелі арқылы тек заңгерлерді жасауға болады',
        'password_create' => 'Минимум 8 таңба',
        'password_edit' => 'Ағымдағы құпия сөзді сақтау үшін бос қалдырыңыз',
        'profession' => 'Заңгердің негізгі мамандығын таңдаңыз',
    ],
    'labels' => [
        'none' => 'Жоқ',
    ],
];

