<?php

return [
    // Кіру
    'login' => [
        'identifier_required' => 'Email немесе телефон нөмірі қажет',
        'password_required' => 'Құпия сөз қажет',
    ],

    // Тіркелу
    'register' => [
        'user_type_required' => 'Пайдаланушы түрі қажет',
        'user_type_in' => 'Пайдаланушы түрі company_admin немесе expert болуы керек',
        'email_required_without' => 'Телефон көрсетілмесе Email қажет',
        'email_unique' => 'Бұл email мекенжайы әлдеқашан тіркелген',
        'phone_required_without' => 'Email көрсетілмесе телефон қажет',
        'phone_unique' => 'Бұл телефон нөмірі әлдеқашан тіркелген',
        'password_min' => 'Құпия сөз кем дегенде 8 таңбадан тұруы керек',
        'password_confirmed' => 'Құпия сөзді растау сәйкес келмейді',
        'country_code_required_with' => 'Телефон көрсетілсе елдің коды қажет',
        'invite_code_required_if' => 'Сарапшы тіркелуі үшін шақыру коды қажет',
        'profession_ids_array' => 'Мамандық ID-лары массив болуы керек',
        'profession_ids_exists' => 'Бір немесе бірнеше мамандық ID-лары жарамсыз',
    ],

    // Профильді жаңарту
    'update_profile' => [
        'email_unique' => 'Бұл email әлдеқашан пайдаланылған',
        'phone_unique' => 'Бұл телефон нөмірі әлдеқашан пайдаланылған',
        'country_code_required_with' => 'Телефон көрсетілсе елдің коды қажет',
        'profession_ids_array' => 'Мамандық ID-лары массив болуы керек',
        'profession_ids_exists' => 'Бір немесе бірнеше мамандық ID-лары жарамсыз',
    ],

    // Ұйым жасау
    'create_organization' => [
        'name_required' => 'Ұйым атауы қажет',
        'name_max' => 'Ұйым атауы 255 таңбадан аспауы керек',
        'company_id_unique' => 'Бұл компания ID-і әлдеқашан тіркелген',
        'company_id_max' => 'Компания ID-і 255 таңбадан аспауы керек',
        'description_max' => 'Сипаттама 1000 таңбадан аспауы керек',
        'contact_name_max' => 'Байланыс аты 255 таңбадан аспауы керек',
        'phone_max' => 'Телефон нөмірі 20 таңбадан аспауы керек',
        'email_email' => 'Жарамды email мекенжайын көрсетіңіз',
        'email_max' => 'Email 255 таңбадан аспауы керек',
        'logo_image' => 'Логотип сурет файлы болуы керек',
        'logo_mimes' => 'Логотип JPEG, PNG, JPG немесе GIF файлы болуы керек',
        'logo_max' => 'Логотип өлшемі 2MB аспауы керек',
    ],

    // Ұйымды жаңарту
    'update_organization' => [
        'name_required' => 'Ұйым атауы қажет',
        'name_max' => 'Ұйым атауы 255 таңбадан аспауы керек',
        'company_id_required' => 'Компания ID-і қажет',
        'company_id_unique' => 'Бұл компания ID-і әлдеқашан тіркелген',
        'company_id_max' => 'Компания ID-і 255 таңбадан аспауы керек',
        'description_max' => 'Сипаттама 1000 таңбадан аспауы керек',
        'contact_name_max' => 'Байланыс аты 255 таңбадан аспауы керек',
        'phone_max' => 'Телефон нөмірі 20 таңбадан аспауы керек',
        'email_email' => 'Жарамды email мекенжайын көрсетіңіз',
        'email_max' => 'Email 255 таңбадан аспауы керек',
        'logo_image' => 'Логотип сурет файлы болуы керек',
        'logo_mimes' => 'Логотип JPEG, PNG, JPG немесе GIF файлы болуы керек',
        'logo_max' => 'Логотип өлшемі 2MB аспауы керек',
        'status_in' => 'Мәртебе active немесе inactive болуы керек',
    ],

    // Шақыру коды жасау
    'generate_invite_code' => [
        'organization_id_required' => 'Ұйым ID-і қажет',
        'organization_id_exists' => 'Ұйым табылмады',
        'user_type_in' => 'Пайдаланушы түрі: expert немесе company_admin болуы керек',
        'permissions_array' => 'Рұқсаттар массив болуы керек',
        'permissions_can_apply_consultation_boolean' => 'Консультация қолдану рұқсаты логикалық мән болуы керек',
        'max_uses_integer' => 'Максималды пайдалану саны бүтін сан болуы керек',
        'max_uses_min' => 'Максималды пайдалану саны кем дегенде 1 болуы керек',
        'max_uses_max' => 'Максималды пайдалану саны 100-ден аспауы керек',
        'expires_at_date' => 'Мерзімі өту күні жарамды күн болуы керек',
        'expires_at_after' => 'Мерзімі өту күні болашақта болуы керек',
    ],

    // Шақыруды тексеру
    'validate_invite' => [
        'invite_code_required' => 'Шақыру коды қажет',
        'invite_code_string' => 'Шақыру коды жол болуы керек',
    ],

    // Консультация жасау
    'store_consultation' => [
        'description_required' => 'Консультация сипаттамасы қажет',
        'topic_type_required' => 'Консультация тақырыбы қажет',
        'topic_type_in' => 'Таңдалған тақырып түрі жарамсыз',
        'priority_in' => 'Таңдалған басымдық жарамсыз',
        'files_file' => 'Әрбір жүктеу жарамды файл болуы керек',
        'files_max' => 'Әрбір файл :max MB аспауы керек',
        'user_type_lawyers_cannot_create' => 'Заңгерлер консультация жасай алмайды',
        'permission_denied' => 'Сізде консультация жасауға рұқсат жоқ',
    ],

    // Консультацияны жаңарту
    'update_consultation' => [
        'title_required' => 'Консультация тақырыбы қажет',
        'description_required' => 'Консультация сипаттамасы қажет',
        'topic_type_in' => 'Таңдалған тақырып түрі жарамсыз',
        'priority_in' => 'Таңдалған басымдық жарамсыз',
    ],

    // Консультация мәртебесін жаңарту
    'update_consultation_status' => [
        'status_required' => 'Мәртебе қажет',
        'status_in' => 'Таңдалған мәртебе жарамсыз',
        'reason_max' => 'Себеп 500 таңбадан аспауы керек',
    ],
];


