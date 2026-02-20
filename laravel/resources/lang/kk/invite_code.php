<?php

return [
    'fields' => [
        'code' => 'Код',
        'organization' => 'Ұйым',
        'created_by' => 'Жасаушы',
        'user_type' => 'Пайдаланушы түрі',
        'permissions' => 'Рұқсаттар',
        'max_uses' => 'Максималды пайдалану саны',
        'used_count' => 'Пайдаланылған',
        'expires_at' => 'Мерзімі аяқталады',
        'status' => 'Күйі',
        'created_at' => 'Жасалған күні',
    ],
    'options' => [
        'user_types' => [
            'expert' => 'Маман',
            'company_admin' => 'Компания әкімшісі',
            'operator' => 'Оператор',
        ],
        'status' => [
            'active' => 'Белсенді',
            'expired' => 'Мерзімі өтті',
        ],
    ],
    'statuses' => [
        'active' => 'Белсенді',
        'expired' => 'Мерзімі өтті',
        'used' => 'Пайдаланылды',
    ],
    'actions' => [
        'delete' => 'Шақыру кодтарын жою',
        'deleted_success' => 'Таңдалған шақыру кодтары жойылды',
    ],
    'help' => [
        'code' => 'Бірегей шақыру коды',
        'user_type' => 'Маман: Шектеулі рұқсаттар, Компания әкімшісі: Ұйымды толық басқару, Оператор: Тағайындалған чаттарға қол жеткізу',
        'can_apply_consultation' => 'Пайдаланушыға кеңес сұрауларын жасауға рұқсат беру (Тек мамандарға)',
        'max_uses' => 'Максималды пайдалану саны',
        'expires_at' => 'Мерзімсіз үшін бос қалдырыңыз',
    ],
    'labels' => [
        'no_uses' => 'Әлі пайдаланылған жоқ',
        'can_apply_consultation_yes' => 'Кеңес сұрауларын жасай алады: Иә',
        'can_apply_consultation_no' => 'Кеңес сұрауларын жасай алады: Жоқ',
        'can_manage_organization_yes' => 'Ұйымды басқара алады: Иә',
        'can_manage_organization_no' => 'Ұйымды басқара алады: Жоқ',
        'can_view_all_consultations_yes' => 'Барлық кеңестерді көре алады: Иә',
        'can_view_all_consultations_no' => 'Барлық кеңестерді көре алады: Жоқ',
    ],
];

