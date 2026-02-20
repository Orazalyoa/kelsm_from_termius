<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationService
{
    /**
     * Create a new organization
     *
     * @param array $data
     * @param int $createdBy
     * @param bool $useTransaction Whether to wrap in transaction (default: true)
     * @return Organization
     */
    public static function create(array $data, $createdBy, $useTransaction = true)
    {
        $data['created_by'] = $createdBy;
        
        // If company_id is not provided, generate one
        if (empty($data['company_id'])) {
            $data['company_id'] = self::generateCompanyId();
        }
        
        $executeCreate = function() use ($data, $createdBy) {
            $organization = Organization::create($data);
            
            // Add creator as owner
            self::addMember($organization->id, $createdBy, 'owner', $createdBy);
            
            return $organization;
        };
        
        if ($useTransaction) {
            DB::beginTransaction();
            try {
                $organization = $executeCreate();
                DB::commit();
                return $organization;
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            return $executeCreate();
        }
    }

    /**
     * Add member to organization
     *
     * @param int $organizationId
     * @param int $userId
     * @param string $role
     * @param int $invitedBy
     * @return void
     */
    public static function addMember($organizationId, $userId, $role = 'member', $invitedBy = null)
    {
        DB::table('organization_members')->updateOrInsert(
            [
                'organization_id' => $organizationId,
                'user_id' => $userId
            ],
            [
                'role' => $role,
                'invited_by' => $invitedBy,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    /**
     * Remove member from organization
     *
     * @param int $organizationId
     * @param int $userId
     * @return bool
     */
    public static function removeMember($organizationId, $userId)
    {
        return DB::table('organization_members')
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Update member role
     *
     * @param int $organizationId
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public static function updateMemberRole($organizationId, $userId, $role)
    {
        return DB::table('organization_members')
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->update([
                'role' => $role,
                'updated_at' => now()
            ]) > 0;
    }

    /**
     * Get organization members
     *
     * @param int $organizationId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMembers($organizationId)
    {
        return DB::table('organization_members')
            ->join('users', 'organization_members.user_id', '=', 'users.id')
            ->where('organization_members.organization_id', $organizationId)
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.phone',
                'users.user_type',
                'users.avatar',
                'users.status',
                'organization_members.role',
                'organization_members.joined_at',
                'organization_members.created_at'
            ])
            ->get();
    }

    /**
     * Check if user is member of organization
     *
     * @param int $organizationId
     * @param int $userId
     * @return bool
     */
    public static function isMember($organizationId, $userId)
    {
        return DB::table('organization_members')
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get user's role in organization
     *
     * @param int $organizationId
     * @param int $userId
     * @return string|null
     */
    public static function getUserRole($organizationId, $userId)
    {
        $member = DB::table('organization_members')
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->first();
            
        return $member ? $member->role : null;
    }

    /**
     * Check if user can manage organization
     * 
     * Requirements:
     * 1. User must be company_admin type (not expert or lawyer)
     * 2. User must be owner or admin in the organization
     * 
     * This prevents experts and lawyers from:
     * - Editing organization info
     * - Creating/managing invite codes
     * - Managing organization members
     *
     * @param int $organizationId
     * @param int $userId
     * @return bool
     */
    public static function canManage($organizationId, $userId)
    {
        $user = User::find($userId);
        
        // Must be company_admin user type (reject expert and lawyer)
        if (!$user || $user->user_type !== User::TYPE_COMPANY_ADMIN) {
            return false;
        }
        
        // Must be owner or admin in the organization
        $role = self::getUserRole($organizationId, $userId);
        return in_array($role, ['owner', 'admin']);
    }

    /**
     * Generate unique company ID
     *
     * @return string
     */
    private static function generateCompanyId()
    {
        do {
            $companyId = '@comp-' . rand(1000, 9999);
        } while (Organization::where('company_id', $companyId)->exists());
        
        return $companyId;
    }

    /**
     * Update organization
     *
     * @param int $organizationId
     * @param array $data
     * @return bool
     */
    public static function update($organizationId, array $data)
    {
        $organization = Organization::findOrFail($organizationId);
        return $organization->update($data);
    }

    /**
     * Get organization statistics
     *
     * @param int $organizationId
     * @return array
     */
    public static function getStatistics($organizationId)
    {
        $memberCount = DB::table('organization_members')
            ->where('organization_id', $organizationId)
            ->count();
            
        $activeInviteCodes = DB::table('invite_codes')
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count();
            
        return [
            'member_count' => $memberCount,
            'active_invite_codes' => $activeInviteCodes
        ];
    }
}
