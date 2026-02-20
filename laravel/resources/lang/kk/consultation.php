<?php

return [
    'title' => 'Тақырып',
    'description' => 'Сипаттама',
    'topic_type' => 'Тақырып түрі',
    'status' => 'Мәртебе',
    'priority' => 'Басымдық',
    'creator' => 'Жасаушы',
    'creator_email' => 'Жасаушының email-і',
    'assigned_lawyer' => 'Тағайындалған заңгер',
    'assigned_lawyers' => 'Тағайындалған заңгерлер',
    'assigned_operators' => 'Тағайындалған операторлар',
    'primary_lawyer' => 'Негізгі заңгер',
    'all_assigned_lawyers' => 'Барлық тағайындалған заңгерлер',
    'all_assigned_operators' => 'Барлық тағайындалған операторлар',
    'lawyer_email' => 'Заңгердің email-і',
    'files_count' => 'Файлдар',
    'chat_room' => 'Чат бөлмесі',
    'created_at' => 'Жасалған уақыт',
    'assigned_at' => 'Тағайындалған уақыт',
    'completed_at' => 'Аяқталған уақыт',
    'unassigned' => 'Тағайындалмаған',
    'no_operators' => 'Операторлар тағайындалмаған',
    'not_created' => 'Жасалмаған',
    'primary_label' => 'Негізгі жауапты',
    'assigned_time' => 'Тағайындалған уақыт',
    
    // Topic types
    'topic_types' => [
        'legal_consultation' => 'Заңды кеңес',
        'contracts_deals' => 'Шарттар/Келісімдер',
        'legal_services' => 'Заңды қызметтер',
        'other' => 'Басқа',
    ],
    
    // Statuses
    'statuses' => [
        'pending' => 'Күту',
        'in_progress' => 'Орындалуда',
        'archived' => 'Мұрағатталған',
        'cancelled' => 'Болдырылмаған',
    ],
    
    // Priorities
    'priorities' => [
        'low' => 'Төмен',
        'medium' => 'Орташа',
        'high' => 'Жоғары',
        'urgent' => 'Шұғыл',
    ],
    
    // Actions
    'actions' => [
        'assign_lawyer' => 'Заңгерді тағайындау',
        'manage_lawyers' => 'Заңгерлерді басқару',
        'manage_operators' => 'Операторларды басқару',
        'complete_consultation' => 'Консультацияны аяқтау',
        'view_files' => 'Файлдар',
        'view_chat' => 'Чатты көру',
        'view_chat_room' => 'Чат бөлмесін ашу',
    ],
    
    // Messages
    'messages' => [
        'enter_lawyer_id' => 'Тағайындау үшін заңгерді таңдаңыз',
        'please_enter_lawyer_id' => 'Заңгерді таңдаңыз',
        'please_select_lawyers' => 'Кемінде бір заңгерді таңдаңыз',
        'please_select_operators' => 'Кемінде бір операторды таңдаңыз',
        'assign_success' => 'Заңгер(лер) сәтті тағайындалды',
        'assign_operator_success' => 'Оператор(лар) сәтті тағайындалды',
        'assign_failed' => 'Тағайындау қатесі',
        'lawyer_invalid' => 'Таңдалған пайдаланушы заңгер емес',
        'complete_success' => 'Консультация аяқталды',
        'invalid_status_for_complete' => 'Тек "Аяқтауды күту" мәртебесіндегі консультацияларды аяқтауға болады',
    ],
    
    // Admin fields
    'admin_notes' => 'Әкімші жазбалары',
    'admin_notes_placeholder' => 'Әкімші жазбаларын енгізіңіз (міндетті емес)',
    
    // Status log
    'status_log' => [
        'title' => 'Мәртебе тарихы',
        'old_status' => 'Ескі мәртебе',
        'new_status' => 'Жаңа мәртебе',
        'changed_by' => 'Өзгерткен',
        'reason' => 'Себеп',
        'new_record' => 'Жаңа',
    ],
    
    // File
    'file' => [
        'title' => 'Файлдар',
        'name' => 'Файл аты',
        'type' => 'Түрі',
        'size' => 'Өлшемі',
        'version' => 'Нұсқа',
        'latest' => 'Соңғы',
        'uploader' => 'Жүктеген',
        'upload_time' => 'Жүктелген уақыт',
        'download' => 'Жүктеп алу',
    ],
    
    // Form help text
    'help' => [
        'select_lawyer' => 'Заңгерді таңдау автоматты түрде чат бөлмесін жасайды',
        'select_multiple_lawyers' => 'Бірнеше заңгерді таңдауға болады. Алғашқы тағайындау кезінде бірінші заңгер негізгі жауапты болады. Негізгі заңгер қалың қаріппен көрсетілген.',
        'select_multiple_operators' => 'Консультацияға бірнеше операторды таңдауға болады.',
        'admin_notes' => 'Әкімші жазбалары күй журналына жазылады (міндетті емес)',
    ],
    
    // Updated time
    'updated_at' => 'Жаңартылған уақыт',
    'time' => 'Уақыт',
];

