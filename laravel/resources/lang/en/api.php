<?php

return [
    // Authentication
    'auth' => [
        'lawyers_admin_only' => 'Lawyers can only be created through admin panel',
        'invalid_invite_code' => 'Invalid invite code',
        'registration_failed' => 'Registration failed',
        'invalid_credentials' => 'Invalid credentials',
        'logged_out' => 'You have been logged out successfully',
        'invalid_expired_invite' => 'Invalid or expired invite code',
        'password_changed' => 'Password changed successfully',
        'current_password_incorrect' => 'Current password is incorrect',
        'otp_sent_failed' => 'Failed to send OTP',
        'otp_invalid_expired' => 'Invalid or expired OTP code',
        'otp_verified' => 'OTP verified successfully',
        'password_reset' => 'Password reset successfully',
        'user_not_found' => 'User not found',
    ],

    // Consultation
    'consultation' => [
        'created' => 'Consultation created successfully',
        'updated' => 'Consultation updated successfully',
        'status_updated' => 'Status updated successfully',
        'unauthorized' => 'Unauthorized access',
        'file_uploaded' => 'File uploaded successfully',
        'file_deleted' => 'File deleted successfully',
        'file_not_found' => 'File not found',
        'forbidden' => 'Forbidden: You do not have access to this consultation',
        'authentication_required' => 'Unauthorized: Authentication required',
        'withdrawn' => 'Consultation withdrawn successfully',
        'priority_escalated' => 'Priority escalated successfully',
        'archived' => 'Consultation archived successfully',
        'unarchived' => 'Consultation restored successfully',
    ],

    // Organization
    'organization' => [
        'created' => 'Organization created successfully',
        'creation_failed' => 'Organization creation failed',
        'updated' => 'Organization updated successfully',
        'update_failed' => 'Organization update failed',
        'forbidden' => 'Forbidden',
        'member_updated' => 'Member role updated successfully',
        'member_update_failed' => 'Member update failed',
        'member_removed' => 'Member removed successfully',
        'member_removal_failed' => 'Member removal failed',
        'member_added' => 'Member added successfully',
        'member_addition_failed' => 'Member addition failed',
        'member_already_exists' => 'User is already a member of this organization',
        'only_owner_delete' => 'Only organization owner can delete the organization',
        'cannot_delete_active' => 'Cannot delete organization with active consultations. Please complete or cancel them first.',
        'deleted' => 'Organization deleted successfully',
        'deletion_failed' => 'Organization deletion failed',
    ],

    // Invite Code
    'invite_code' => [
        'generated' => 'Invite code generated successfully',
        'generation_failed' => 'Invite code generation failed',
        'deleted' => 'Invite code deleted successfully',
        'deletion_failed' => 'Invite code deletion failed',
        'forbidden' => 'Forbidden',
        'batch_created' => 'Successfully created :count invite codes',
        'batch_creation_failed' => 'Batch creation failed',
        'organization_id_required' => 'organization_id is required',
        'invalid_expired' => 'Invalid or expired invite code',
    ],

    // User
    'user' => [
        'profile_updated' => 'Profile updated successfully',
        'profile_update_failed' => 'Profile update failed',
        'avatar_uploaded' => 'Avatar uploaded successfully',
        'invalid_password' => 'Invalid password',
        'account_deleted' => 'Account deleted successfully',
        'account_deletion_failed' => 'Account deletion failed',
        'notification_settings_updated' => 'Notification settings updated successfully',
        'privacy_settings_updated' => 'Privacy settings updated successfully',
    ],

    // Common
    'common' => [
        'success' => 'Operation completed successfully',
        'failed' => 'Operation failed',
        'forbidden' => 'Forbidden',
        'unauthorized' => 'Unauthorized',
        'not_found' => 'Resource not found',
        'validation_error' => 'Validation error',
        'server_error' => 'Server error',
    ],
];

