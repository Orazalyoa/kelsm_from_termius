<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateInviteCodeRequest;
use App\Http\Resources\InviteCodeResource;
use App\Models\InviteCode;
use App\Models\InviteCodeUse;
use App\Services\InviteCodeService;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InviteCodeController extends Controller
{
    /**
     * Get invite codes for user's organizations
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $organizationId = $request->get('organization_id');
        
        if ($organizationId) {
            // Check if user can manage this organization
            if (!OrganizationService::canManage($organizationId, $user->id)) {
                return response()->json(['error' => __('api.invite_code.forbidden')], 403);
            }
            $inviteCodes = InviteCodeService::getForOrganization($organizationId, $user->id);
        } else {
            // Get all invite codes for user's organizations
            $organizationIds = $user->organizations()->pluck('organizations.id');
            $inviteCodes = collect();
            foreach ($organizationIds as $orgId) {
                $inviteCodes = $inviteCodes->merge(
                    InviteCodeService::getForOrganization($orgId, $user->id)
                );
            }
        }
        
        return response()->json([
            'invite_codes' => InviteCodeResource::collection($inviteCodes)
        ]);
    }

    /**
     * Generate new invite code
     */
    public function store(GenerateInviteCodeRequest $request)
    {
        $user = auth('api')->user();
        $organizationId = $request->organization_id;
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($organizationId, $user->id)) {
            return response()->json(['error' => __('api.invite_code.forbidden')], 403);
        }
        
        try {
            $inviteCode = InviteCodeService::generate(
                $organizationId,
                $user->id,
                $request->only(['user_type', 'permissions', 'max_uses', 'expires_at'])
            );
            
            return response()->json([
                'message' => __('api.invite_code.generated'),
                'invite_code' => new InviteCodeResource($inviteCode)
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.invite_code.generation_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete invite code
     */
    public function destroy($id)
    {
        $user = auth('api')->user();
        $inviteCode = InviteCode::findOrFail($id);
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($inviteCode->organization_id, $user->id)) {
            return response()->json(['error' => __('api.invite_code.forbidden')], 403);
        }
        
        try {
            InviteCodeService::delete($id);
            
            return response()->json([
                'message' => __('api.invite_code.deleted')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.invite_code.deletion_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get invite code details
     */
    public function show($id)
    {
        $user = auth('api')->user();
        $inviteCode = InviteCode::findOrFail($id);
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($inviteCode->organization_id, $user->id)) {
            return response()->json(['error' => __('api.invite_code.forbidden')], 403);
        }

        $inviteCode->load(['organization', 'createdBy', 'uses.user']);

        return response()->json([
            'invite_code' => new InviteCodeResource($inviteCode)
        ]);
    }

    /**
     * Batch create invite codes
     */
    public function batchCreate(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'count' => 'required|integer|min:1|max:100',
            'user_type' => 'nullable|in:expert,company_admin',
            'permissions' => 'nullable|array',
            'permissions.can_apply_consultation' => 'nullable|boolean',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = auth('api')->user();
        $organizationId = $request->organization_id;
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($organizationId, $user->id)) {
            return response()->json(['error' => __('api.invite_code.forbidden')], 403);
        }

        try {
            $inviteCodes = [];
            $options = $request->only(['user_type', 'permissions', 'max_uses', 'expires_at']);

            for ($i = 0; $i < $request->count; $i++) {
                $inviteCode = InviteCodeService::generate(
                    $organizationId,
                    $user->id,
                    $options
                );
                $inviteCodes[] = $inviteCode;
            }

            return response()->json([
                'message' => __('api.invite_code.batch_created', ['count' => $request->count]),
                'invite_codes' => InviteCodeResource::collection($inviteCodes)
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => __('api.invite_code.batch_creation_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get invite code uses (usage history)
     */
    public function uses(Request $request, $id)
    {
        $user = auth('api')->user();
        $inviteCode = InviteCode::findOrFail($id);
        
        // Check if user can manage this organization
        if (!OrganizationService::canManage($inviteCode->organization_id, $user->id)) {
            return response()->json(['error' => __('api.invite_code.forbidden')], 403);
        }

        $perPage = $request->get('per_page', 20);
        $uses = InviteCodeUse::where('invite_code_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($uses);
    }

    /**
     * Get invite code statistics
     */
    public function stats(Request $request)
    {
        $user = auth('api')->user();
        $organizationId = $request->get('organization_id');

        if (!$organizationId) {
            return response()->json(['error' => __('api.invite_code.organization_id_required')], 422);
        }

        // Check if user can manage this organization
        if (!OrganizationService::canManage($organizationId, $user->id)) {
            return response()->json(['error' => __('api.invite_code.forbidden')], 403);
        }

        $stats = [
            'total' => InviteCode::where('organization_id', $organizationId)->count(),
            'active' => InviteCode::where('organization_id', $organizationId)
                ->where('status', InviteCode::STATUS_ACTIVE)
                ->whereRaw('used_count < max_uses')
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->count(),
            'expired' => InviteCode::where('organization_id', $organizationId)
                ->where('expires_at', '<=', now())
                ->count(),
            'used' => InviteCode::where('organization_id', $organizationId)
                ->whereRaw('used_count >= max_uses')
                ->count(),
            'total_uses' => InviteCodeUse::whereIn('invite_code_id', function($query) use ($organizationId) {
                $query->select('id')
                      ->from('invite_codes')
                      ->where('organization_id', $organizationId);
            })->count(),
            'unique_users' => InviteCodeUse::whereIn('invite_code_id', function($query) use ($organizationId) {
                $query->select('id')
                      ->from('invite_codes')
                      ->where('organization_id', $organizationId);
            })->distinct('user_id')->count('user_id'),
        ];

        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Export invite codes to CSV
     */
    public function export(Request $request)
    {
        $user = auth('api')->user();
        $organizationId = $request->get('organization_id');

        if (!$organizationId) {
            return response()->json(['error' => __('api.invite_code.organization_id_required')], 422);
        }

        // Check if user can manage this organization
        if (!OrganizationService::canManage($organizationId, $user->id)) {
            return response()->json(['error' => __('api.invite_code.forbidden')], 403);
        }

        $query = InviteCode::where('organization_id', $organizationId)
            ->with(['organization', 'createdBy']);

        // Apply status filter if provided
        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('status', 'active')
                      ->whereRaw('used_count < max_uses')
                      ->where(function($q) {
                          $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                      });
            } elseif ($status === 'expired') {
                $query->where('expires_at', '<=', now());
            } elseif ($status === 'used') {
                $query->whereRaw('used_count >= max_uses');
            }
        }

        $inviteCodes = $query->get();

        // Generate CSV
        $csv = "Code,Organization,Created By,Max Uses,Times Used,Expires At,Status,Created At\n";
        
        foreach ($inviteCodes as $code) {
            // Determine status
            if ($code->max_uses && $code->used_count >= $code->max_uses) {
                $status = 'Used';
            } elseif ($code->expires_at && $code->expires_at->isPast()) {
                $status = 'Expired';
            } else {
                $status = 'Active';
            }

            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $code->code,
                $code->organization->name ?? 'N/A',
                $code->createdBy->full_name ?? 'N/A',
                $code->max_uses ?? 'Unlimited',
                $code->times_used,
                $code->expires_at ? $code->expires_at->format('Y-m-d H:i:s') : 'Never',
                $status,
                $code->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invite_codes_' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Check if invite code is valid
     */
    public function check(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $inviteCode = InviteCodeService::validate($request->code);

        if (!$inviteCode) {
            return response()->json([
                'valid' => false,
                'error' => __('api.invite_code.invalid_expired')
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'organization' => [
                'id' => $inviteCode->organization->id,
                'name' => $inviteCode->organization->name,
                'company_id' => $inviteCode->organization->company_id,
            ]
        ]);
    }
}