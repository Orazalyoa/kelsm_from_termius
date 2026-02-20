<?php

namespace App\Services;

use App\Models\InviteCode;
use App\Models\InviteCodeUse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InviteCodeService
{
    /**
     * Generate a new invite code
     *
     * @param int $organizationId
     * @param int $createdBy
     * @param array $options
     * @return InviteCode
     */
    public static function generate($organizationId, $createdBy, $options = [])
    {
        $code = strtoupper(Str::random(10));
        
        // Ensure code is unique
        while (InviteCode::where('code', $code)->exists()) {
            $code = strtoupper(Str::random(10));
        }
        
        $userType = $options['user_type'] ?? InviteCode::USER_TYPE_EXPERT;
        
        // Set default permissions based on user type
        $permissions = $options['permissions'] ?? [];
        if ($userType === InviteCode::USER_TYPE_COMPANY_ADMIN) {
            // Company admin has all permissions
            $permissions = [
                'can_apply_consultation' => true,
                'can_manage_organization' => true,
                'can_view_all_consultations' => true
            ];
        } else {
            // Expert permissions
            $permissions = [
                'can_apply_consultation' => $permissions['can_apply_consultation'] ?? true
            ];
        }
        
        return InviteCode::create([
            'code' => $code,
            'organization_id' => $organizationId,
            'created_by' => $createdBy,
            'user_type' => $userType,
            'permissions' => $permissions,
            'max_uses' => $options['max_uses'] ?? 1,
            'expires_at' => $options['expires_at'] ?? now()->addDays(30),
            'status' => InviteCode::STATUS_ACTIVE
        ]);
    }

    /**
     * Validate an invite code
     *
     * @param string $code
     * @return InviteCode|null
     */
    public static function validate($code)
    {
        return InviteCode::where('code', $code)
            ->where('status', InviteCode::STATUS_ACTIVE)
            ->where('used_count', '<', DB::raw('max_uses'))
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with('organization')
            ->first();
    }

    /**
     * Mark invite code as used
     *
     * @param int $inviteCodeId
     * @param int $userId
     * @return void
     */
    public static function markUsed($inviteCodeId, $userId)
    {
        // No transaction here - let the caller handle transactions
        $invite = InviteCode::findOrFail($inviteCodeId);
        $invite->increment('used_count');
        
        InviteCodeUse::create([
            'invite_code_id' => $inviteCodeId,
            'user_id' => $userId,
            'used_at' => now()
        ]);
        
        // No need to update status when max uses reached
        // Status will be determined by used_count >= max_uses check
    }

    /**
     * Delete an invite code
     *
     * @param int $inviteCodeId
     * @return bool
     */
    public static function delete($inviteCodeId)
    {
        $invite = InviteCode::findOrFail($inviteCodeId);
        return $invite->delete();
    }

    /**
     * Get invite codes for organization
     *
     * @param int $organizationId
     * @param int $createdBy
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getForOrganization($organizationId, $createdBy = null)
    {
        $query = InviteCode::where('organization_id', $organizationId);
        
        if ($createdBy) {
            $query->where('created_by', $createdBy);
        }
        
        return $query->with(['organization', 'creator', 'uses.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if invite code is expired
     *
     * @param InviteCode $inviteCode
     * @return bool
     */
    public static function isExpired(InviteCode $inviteCode)
    {
        return $inviteCode->expires_at && $inviteCode->expires_at->isPast();
    }

    /**
     * Clean up expired invite codes
     *
     * @return int Number of codes updated
     */
    public static function cleanupExpired()
    {
        return InviteCode::where('status', InviteCode::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->update(['status' => InviteCode::STATUS_EXPIRED]);
    }
}
