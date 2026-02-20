<?php

return [
    'title' => 'Title',
    'description' => 'Description',
    'topic_type' => 'Topic Type',
    'status' => 'Status',
    'priority' => 'Priority',
    'creator' => 'Creator',
    'creator_email' => 'Creator Email',
    'assigned_lawyer' => 'Assigned Lawyer',
    'assigned_lawyers' => 'Assigned Lawyers',
    'assigned_operators' => 'Assigned Operators',
    'primary_lawyer' => 'Primary Lawyer',
    'all_assigned_lawyers' => 'All Assigned Lawyers',
    'all_assigned_operators' => 'All Assigned Operators',
    'lawyer_email' => 'Lawyer Email',
    'files_count' => 'Files',
    'chat_room' => 'Chat Room',
    'created_at' => 'Created At',
    'assigned_at' => 'Assigned At',
    'archived_at' => 'Archived At',
    'unassigned' => 'Unassigned',
    'no_operators' => 'No operators assigned',
    'not_created' => 'Not Created',
    
    // Topic types
    'topic_types' => [
        'legal_consultation' => 'Legal Consultation',
        'contracts_deals' => 'Contracts/Deals',
        'legal_services' => 'Legal Services',
        'other' => 'Other',
    ],
    
    // Statuses
    'statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'archived' => 'Archived',
        'cancelled' => 'Cancelled',
    ],
    
    // Priorities
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    
    // Actions
    'actions' => [
        'assign_lawyer' => 'Assign Lawyer',
        'manage_lawyers' => 'Manage Lawyers',
        'manage_operators' => 'Manage Operators',
        'view_files' => 'Files',
        'view_chat' => 'View Chat',
        'view_chat_room' => 'View Chat Room',
    ],
    
    // Messages
    'messages' => [
        'enter_lawyer_id' => 'Select a lawyer to assign',
        'please_enter_lawyer_id' => 'Please select a lawyer',
        'please_select_lawyers' => 'Please select at least one lawyer',
        'please_select_operators' => 'Please select at least one operator',
        'assign_success' => 'Lawyer(s) assigned successfully',
        'assign_operator_success' => 'Operator(s) assigned successfully',
        'assign_failed' => 'Assignment failed',
        'lawyer_invalid' => 'Selected user is not a lawyer',
    ],
    
    // Admin fields
    'admin_notes' => 'Admin Notes',
    'admin_notes_placeholder' => 'Enter admin notes (optional)',
    
    // Status log
    'status_log' => [
        'title' => 'Status History',
        'old_status' => 'Old Status',
        'new_status' => 'New Status',
        'changed_by' => 'Changed By',
        'reason' => 'Reason',
        'new_record' => 'New',
    ],
    
    // File
    'file' => [
        'title' => 'Files',
        'name' => 'File Name',
        'type' => 'Type',
        'size' => 'Size',
        'version' => 'Version',
        'latest' => 'Latest',
        'uploader' => 'Uploader',
        'upload_time' => 'Upload Time',
        'download' => 'Download',
    ],
    
    // Form help text
    'help' => [
        'select_lawyer' => 'Selecting a lawyer will automatically create a chat room',
        'select_multiple_lawyers' => 'You can select multiple lawyers. The first lawyer will become the primary responsible person on first assignment. Primary lawyer shown in bold.',
        'select_multiple_operators' => 'You can select multiple operators for this consultation.',
        'admin_notes' => 'Admin notes will be recorded in status log (optional)',
    ],
    
    // Updated time
    'updated_at' => 'Updated At',
    'time' => 'Time',
];

