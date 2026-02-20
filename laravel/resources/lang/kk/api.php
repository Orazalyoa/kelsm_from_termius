<?php

return [
    // Аутентификация
    'auth' => [
        'lawyers_admin_only' => 'Заңгерлер тек әкімші панелі арқылы жасалады',
        'invalid_invite_code' => 'Жарамсыз шақыру коды',
        'registration_failed' => 'Тіркелу қатесі',
        'invalid_credentials' => 'Жарамсыз тіркелгі деректері',
        'logged_out' => 'Жүйеден сәтті шықтыңыз',
        'invalid_expired_invite' => 'Жарамсыз немесе мерзімі өткен шақыру коды',
        'password_changed' => 'Құпия сөз сәтті өзгертілді',
        'current_password_incorrect' => 'Ағымдағы құпия сөз қате',
        'otp_sent_failed' => 'OTP жіберу қатесі',
        'otp_invalid_expired' => 'Жарамсыз немесе мерзімі өткен OTP коды',
        'otp_verified' => 'OTP сәтті расталды',
        'password_reset' => 'Құпия сөз сәтті қалпына келтірілді',
        'user_not_found' => 'Пайдаланушы табылмады',
    ],

    // Консультация
    'consultation' => [
        'created' => 'Консультация сәтті жасалды',
        'updated' => 'Консультация сәтті жаңартылды',
        'status_updated' => 'Мәртебе сәтті жаңартылды',
        'unauthorized' => 'Рұқсат жоқ',
        'file_uploaded' => 'Файл сәтті жүктелді',
        'file_deleted' => 'Файл сәтті жойылды',
        'file_not_found' => 'Файл табылмады',
        'forbidden' => 'Тыйым салынған: Бұл консультацияға қол жеткізуге рұқсатыңыз жоқ',
        'authentication_required' => 'Рұқсат жоқ: Аутентификация қажет',
        'withdrawn' => 'Консультация сәтті қайтарып алынды',
        'priority_escalated' => 'Басымдық сәтті арттырылды',
        'archived' => 'Консультация сәтті мұрағатталды',
        'unarchived' => 'Консультация сәтті қалпына келтірілді',
    ],

    // Ұйым
    'organization' => [
        'created' => 'Ұйым сәтті жасалды',
        'creation_failed' => 'Ұйым жасау қатесі',
        'updated' => 'Ұйым сәтті жаңартылды',
        'update_failed' => 'Ұйым жаңарту қатесі',
        'forbidden' => 'Тыйым салынған',
        'member_updated' => 'Мүше рөлі сәтті жаңартылды',
        'member_update_failed' => 'Мүшені жаңарту қатесі',
        'member_removed' => 'Мүше сәтті алынды',
        'member_removal_failed' => 'Мүшені алу қатесі',
        'member_added' => 'Мүше сәтті қосылды',
        'member_addition_failed' => 'Мүшені қосу қатесі',
        'member_already_exists' => 'Пайдаланушы бұл ұйымның мүшесі болып табылады',
        'only_owner_delete' => 'Ұйымды тек иесі ғана жоя алады',
        'cannot_delete_active' => 'Белсенді консультациялары бар ұйымды жою мүмкін емес. Алдымен оларды аяқтаңыз немесе болдырмаңыз.',
        'deleted' => 'Ұйым сәтті жойылды',
        'deletion_failed' => 'Ұйымды жою қатесі',
    ],

    // Шақыру коды
    'invite_code' => [
        'generated' => 'Шақыру коды сәтті жасалды',
        'generation_failed' => 'Шақыру кодын жасау қатесі',
        'deleted' => 'Шақыру коды сәтті жойылды',
        'deletion_failed' => 'Шақыру кодын жою қатесі',
        'forbidden' => 'Тыйым салынған',
        'batch_created' => ':count шақыру коды сәтті жасалды',
        'batch_creation_failed' => 'Топтама жасау қатесі',
        'organization_id_required' => 'organization_id міндетті',
        'invalid_expired' => 'Жарамсыз немесе мерзімі өткен шақыру коды',
    ],

    // Пайдаланушы
    'user' => [
        'profile_updated' => 'Профиль сәтті жаңартылды',
        'profile_update_failed' => 'Профильді жаңарту қатесі',
        'avatar_uploaded' => 'Аватар сәтті жүктелді',
        'invalid_password' => 'Қате құпия сөз',
        'account_deleted' => 'Аккаунт сәтті жойылды',
        'account_deletion_failed' => 'Аккаунтты жою қатесі',
        'notification_settings_updated' => 'Хабарландыру параметрлері сәтті жаңартылды',
        'privacy_settings_updated' => 'Құпиялылық параметрлері сәтті жаңартылды',
    ],

    // Жалпы
    'common' => [
        'success' => 'Операция сәтті аяқталды',
        'failed' => 'Операция қатесі',
        'forbidden' => 'Тыйым салынған',
        'unauthorized' => 'Рұқсат жоқ',
        'not_found' => 'Ресурс табылмады',
        'validation_error' => 'Тексеру қатесі',
        'server_error' => 'Сервер қатесі',
    ],
];


