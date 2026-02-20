<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use App\Models\Consultation;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    /**
     * Get user's organizations
     */
    public function index()
    {
        $user = auth('api')->user();
        $organizations = $user->organizations()->get();
        
        return response()->json([
            'organizations' => OrganizationResource::collection($organizations)
        ]);
    }

    /**
     * Create new organization
     */
    public function store(CreateOrganizationRequest $request)
    {
        try {
            $organization = OrganizationService::create($request->validated(), auth('api')->id());
            
            return response()->json([
                'message' => __('api.organization.created'),
                'organization' => new OrganizationResource($organization)
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.organization.creation_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get organization details
     */
    public function show($id)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);
        
        return response()->json([
            'organization' => new OrganizationResource($organization)
        ]);
    }

    /**
     * Update organization
     */
    public function update(UpdateOrganizationRequest $request, $id)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($organization->id, $user->id)) {
            return response()->json(['error' => __('api.organization.forbidden')], 403);
        }
        
        try {
            OrganizationService::update($organization->id, $request->validated());
            
            return response()->json([
                'message' => __('api.organization.updated'),
                'organization' => new OrganizationResource($organization->fresh())
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.organization.update_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get organization members
     */
    public function members($id)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);
        
        // Any organization member can view the members list
        $members = OrganizationService::getMembers($organization->id);
        
        return response()->json([
            'members' => $members
        ]);
    }

    /**
     * Update member role
     */
    public function updateMember(Request $request, $id, $userId)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($organization->id, $user->id)) {
            return response()->json(['error' => __('api.organization.forbidden')], 403);
        }
        
        $request->validate([
            'role' => 'required|in:owner,admin,member,lawyer'
        ]);
        
        try {
            OrganizationService::updateMemberRole($organization->id, $userId, $request->role);
            
            return response()->json([
                'message' => __('api.organization.member_updated')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.organization.member_update_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove member from organization
     */
    public function removeMember($id, $userId)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($organization->id, $user->id)) {
            return response()->json(['error' => __('api.organization.forbidden')], 403);
        }
        
        try {
            OrganizationService::removeMember($organization->id, $userId);
            
            return response()->json([
                'message' => __('api.organization.member_removed')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.organization.member_removal_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add member to organization directly
     */
    public function addMember(Request $request, $id)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($organization->id, $user->id)) {
            return response()->json(['error' => __('api.organization.forbidden')], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'string|in:owner,admin,member,lawyer'
        ]);

        try {
            // Check if user is already a member
            $exists = DB::table('organization_members')
                ->where('organization_id', $organization->id)
                ->where('user_id', $request->user_id)
                ->exists();

            if ($exists) {
                return response()->json(['error' => __('api.organization.member_already_exists')], 422);
            }

            OrganizationService::addMember(
                $organization->id,
                $request->user_id,
                $request->role ?? 'member',
                $user->id
            );
            
            return response()->json([
                'message' => __('api.organization.member_added')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.organization.member_addition_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete organization
     */
    public function destroy($id)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);
        
        // Check if user is owner
        $pivot = DB::table('organization_members')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot || $pivot->role !== 'owner') {
            return response()->json(['error' => __('api.organization.only_owner_delete')], 403);
        }

        try {
            DB::beginTransaction();

            // Check if organization has active consultations
            $memberIds = DB::table('organization_members')
                ->where('organization_id', $organization->id)
                ->pluck('user_id');

            $activeConsultations = Consultation::whereIn('created_by', $memberIds)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            if ($activeConsultations > 0) {
                return response()->json([
                    'error' => __('api.organization.cannot_delete_active')
                ], 422);
            }

            // Delete organization members
            DB::table('organization_members')
                ->where('organization_id', $organization->id)
                ->delete();

            // Delete invite codes
            DB::table('invite_codes')
                ->where('organization_id', $organization->id)
                ->delete();

            // Delete organization
            $organization->delete();

            DB::commit();

            return response()->json([
                'message' => __('api.organization.deleted')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => __('api.organization.deletion_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get organization statistics
     */
    public function stats($id)
    {
        $user = auth('api')->user();
        $organization = $user->organizations()->findOrFail($id);

        // Get all member IDs of the organization
        $memberIds = DB::table('organization_members')
            ->where('organization_id', $organization->id)
            ->pluck('user_id');

        $stats = [
            'members' => [
                'total' => $memberIds->count(),
                'by_role' => DB::table('organization_members')
                    ->where('organization_id', $organization->id)
                    ->select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->get()
                    ->pluck('count', 'role')
                    ->toArray(),
            ],
            'consultations' => [
                'total' => Consultation::whereIn('created_by', $memberIds)->count(),
                'pending' => Consultation::whereIn('created_by', $memberIds)
                    ->where('status', 'pending')->count(),
                'in_progress' => Consultation::whereIn('created_by', $memberIds)
                    ->where('status', 'in_progress')->count(),
                'archived' => Consultation::whereIn('created_by', $memberIds)
                    ->where('status', 'archived')->count(),
            ],
            'invite_codes' => [
                'total' => DB::table('invite_codes')
                    ->where('organization_id', $organization->id)
                    ->count(),
                'active' => DB::table('invite_codes')
                    ->where('organization_id', $organization->id)
                    ->where('status', 'active')
                    ->where(function($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    })
                    ->count(),
                'used' => DB::table('invite_code_uses')
                    ->whereIn('invite_code_id', function($query) use ($organization) {
                        $query->select('id')
                              ->from('invite_codes')
                              ->where('organization_id', $organization->id);
                    })
                    ->count(),
            ],
            'created_at' => $organization->created_at->toDateString(),
        ];

        return response()->json([
            'stats' => $stats
        ]);
    }
}