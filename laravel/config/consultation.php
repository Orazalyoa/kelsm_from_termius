<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | The maximum file size in bytes that can be uploaded to a consultation.
    | Default: 536870912 bytes (512 MB)
    |
    */

    'max_file_size' => env('CONSULTATION_MAX_FILE_SIZE', 536870912),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | The file extensions that are allowed to be uploaded.
    |
    */

    'allowed_file_types' => explode(',', env('CONSULTATION_ALLOWED_FILE_TYPES', 'doc,docx,pdf,jpg,jpeg,png,zip')),

    /*
    |--------------------------------------------------------------------------
    | Consultation Statuses
    |--------------------------------------------------------------------------
    |
    | Available statuses for consultations.
    |
    */

    'statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'archived' => 'Archived',
        'cancelled' => 'Cancelled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Consultation Topics
    |--------------------------------------------------------------------------
    |
    | Available topic types for consultations.
    |
    */

    'topics' => [
        'legal_consultation' => 'Legal Consultation',
        'contracts_deals' => 'Contracts & Deals',
        'legal_services' => 'Legal Services',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Consultation Priorities
    |--------------------------------------------------------------------------
    |
    | Available priority levels for consultations.
    |
    */

    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Priority
    |--------------------------------------------------------------------------
    |
    | The default priority level when creating a consultation.
    |
    */

    'default_priority' => 'medium',

];

