<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Http\Requests\UpdateConsultationStatusRequest;
use App\Http\Requests\WithdrawConsultationRequest;
use App\Http\Requests\EscalatePriorityRequest;
use App\Models\Consultation;
use App\Models\ConsultationFile;
use App\Services\ConsultationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsultationController extends Controller
{
    protected $consultationService;

    public function __construct(ConsultationService $consultationService)
    {
        $this->consultationService = $consultationService;
    }

    /**
     * Display a listing of consultations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Consultation::query();

        // Filter by user type
        if ($user->isLawyer()) {
            // Lawyers see consultations assigned to them
            $query->forLawyer($user->id);
        } elseif ($user->isCompanyAdmin()) {
            // Company admin sees all consultations from their company
            $companyIds = $user->organizations()->pluck('company_id')->toArray();
            if (!empty($companyIds)) {
                $query->where(function ($q) use ($companyIds) {
                    foreach ($companyIds as $companyId) {
                        $q->orWhereHas('creator.organizations', function ($subQ) use ($companyId) {
                            $subQ->where('company_id', $companyId);
                        });
                    }
                });
            } else {
                // If company admin has no organizations, fall back to their own consultations
                $query->forUser($user->id);
            }
        } else {
            // Others see consultations they created
            $query->forUser($user->id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('topic_type')) {
            $query->byTopic($request->topic_type);
        }

        // Sort - validate sort field to prevent SQL injection
        $allowedSortFields = ['created_at', 'updated_at', 'status', 'priority', 'title'];
        $sortBy = $request->get('sort_by', 'created_at');
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtolower($request->get('sort_order', 'desc'));
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        $query->orderBy($sortBy, $sortOrder);

        // Paginate - limit per_page to prevent excessive queries
        $perPage = min((int)$request->get('per_page', 15), 100);
        if ($perPage <= 0) {
            $perPage = 15;
        }
        $consultations = $query->with([
            'creator.organizations',
            'assignedLawyer',
            'lawyers',
            'chat',
            'files' => function ($query) {
                $query->rootFiles()->latest();
            }
        ])->paginate($perPage);

        // Add computed fields for frontend
        $consultations->getCollection()->transform(function ($consultation) {
            $consultation->chatAvailable = !!$consultation->chat_id;
            $consultation->creatorName = $consultation->creator ? $consultation->creator->full_name : '';
            return $consultation;
        });

        return response()->json([
            'success' => true,
            'data' => $consultations,
        ]);
    }

    /**
     * Store a newly created consultation.
     *
     * @param StoreConsultationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreConsultationRequest $request)
    {
        try {
            // Handle files - can be array or single file
            $files = [];
            if ($request->hasFile('files')) {
                $uploadedFiles = $request->file('files');
                // Ensure it's an array
                $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
            }
            
            $consultation = $this->consultationService->createConsultation(
                $request->user(),
                $request->validated(),
                $files
            );

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.created'),
                'data' => $consultation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified consultation.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $consultation = Consultation::with([
            'creator.organizations',
            'assignedLawyer',
            'chat.participants.user',
            'files.uploadedBy',
            'deliverables.uploadedBy',
            'deliveredFiles.uploadedBy',
            'statusLogs.changedBy'
        ])->findOrFail($id);

        // Check access permission
        if (!$consultation->canBeAccessedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => __('api.consultation.unauthorized'),
            ], 403);
        }

        // Add computed fields
        $consultation->chatAvailable = !!$consultation->chat_id;
        $consultation->creatorName = $consultation->creator ? $consultation->creator->full_name : '';

        // Add signed download URLs for files
        if ($consultation->files) {
            foreach ($consultation->files as $file) {
                $file->download_url = \URL::temporarySignedRoute(
                    'consultation.file.download',
                    now()->addHours(1),
                    ['id' => $id, 'fileId' => $file->id]
                );
            }
        }

        // Add signed download URLs for deliverables
        if ($consultation->deliverables) {
            foreach ($consultation->deliverables as $file) {
                $file->download_url = \URL::temporarySignedRoute(
                    'consultation.file.download',
                    now()->addHours(1),
                    ['id' => $id, 'fileId' => $file->id]
                );
            }
        }

        // Add signed download URLs for delivered files
        if ($consultation->deliveredFiles) {
            foreach ($consultation->deliveredFiles as $file) {
                $file->download_url = \URL::temporarySignedRoute(
                    'consultation.file.download',
                    now()->addHours(1),
                    ['id' => $id, 'fileId' => $file->id]
                );
            }
        }

        return response()->json([
            'success' => true,
            'data' => $consultation,
        ]);
    }

    /**
     * Update the specified consultation.
     *
     * @param UpdateConsultationRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateConsultationRequest $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);
            
            // Check access permission
            if (!$consultation->canBeAccessedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.consultation.unauthorized'),
                ], 403);
            }

            $consultation->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.updated'),
                'data' => $consultation->load(['creator', 'files']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update consultation status.
     *
     * @param UpdateConsultationStatusRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(UpdateConsultationStatusRequest $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);
            
            $this->consultationService->updateStatus(
                $consultation,
                $request->status,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.status_updated'),
                'data' => $consultation->fresh(['statusLogs']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Upload a file to consultation.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:' . (config('consultation.max_file_size') / 1024),
            'version_notes' => 'nullable|string|max:500',
            'file_category' => 'nullable|in:attachment,deliverable,supplement',
        ]);

        try {
            $consultation = Consultation::findOrFail($id);

            // Check access permission
            if (!$consultation->canBeAccessedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.consultation.unauthorized'),
                ], 403);
            }

            $file = $this->consultationService->uploadFile(
                $consultation,
                $request->file('file'),
                $request->user(),
                $request->version_notes,
                $request->file_category ?? 'attachment'
            );

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.file_uploaded'),
                'data' => $file,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download a consultation file.
     *
     * @param Request $request
     * @param int $id
     * @param int $fileId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadFile(Request $request, $id, $fileId)
    {
        $consultation = Consultation::findOrFail($id);
        $file = ConsultationFile::where('consultation_id', $id)
            ->where('id', $fileId)
            ->firstOrFail();

        // Check authentication: either valid signature or authenticated user
        $hasValidSignature = $request->hasValidSignature();
        $user = $request->user();
        
        if (!$hasValidSignature && !$user) {
            return response()->json([
                'success' => false,
                'message' => __('api.consultation.authentication_required'),
            ], 401);
        }

        // If authenticated via JWT, check access permission
        if ($user && !$consultation->canBeAccessedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => __('api.consultation.forbidden'),
            ], 403);
        }

        // ⚠️ Deliverable access control
        if ($file->is_deliverable) {
            // If user is client
            if ($user && $consultation->created_by === $user->id) {
                // Client can only download delivered deliverables
                if (!$file->can_client_access || is_null($file->delivered_at)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('api.consultation.deliverable_not_delivered'),
                    ], 403);
                }
            }
            // Lawyer and admin can download any file (no restriction)
        }

        $filePath = storage_path('app/public/' . $file->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => __('api.consultation.file_not_found'),
            ], 404);
        }

        return response()->download($filePath, $file->file_name);
    }

    /**
     * Get file versions.
     *
     * @param Request $request
     * @param int $id
     * @param int $fileId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFileVersions(Request $request, $id, $fileId)
    {
        $consultation = Consultation::findOrFail($id);
        
        // Check access permission
        if (!$consultation->canBeAccessedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => __('api.consultation.unauthorized'),
            ], 403);
        }

        $file = ConsultationFile::where('consultation_id', $id)
            ->where('id', $fileId)
            ->firstOrFail();

        $versions = $file->getAllVersions();

        return response()->json([
            'success' => true,
            'data' => $versions,
        ]);
    }

    /**
     * Delete a consultation file.
     *
     * @param Request $request
     * @param int $id
     * @param int $fileId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFile(Request $request, $id, $fileId)
    {
        try {
            $consultation = Consultation::findOrFail($id);
            
            // Check access permission (only creator can delete)
            if ($consultation->created_by !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.consultation.unauthorized'),
                ], 403);
            }

            $file = ConsultationFile::where('consultation_id', $id)
                ->where('id', $fileId)
                ->firstOrFail();

            $this->consultationService->deleteFile($file);

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.file_deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get consultation statistics for the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        if ($user->isLawyer()) {
            $query = Consultation::forLawyer($user->id);
        } elseif ($user->isCompanyAdmin()) {
            // Company admin sees all consultations from their company
            $companyIds = $user->organizations()->pluck('company_id')->toArray();
            if (!empty($companyIds)) {
                $query = Consultation::query()->where(function ($q) use ($companyIds) {
                    foreach ($companyIds as $companyId) {
                        $q->orWhereHas('creator.organizations', function ($subQ) use ($companyId) {
                            $subQ->where('company_id', $companyId);
                        });
                    }
                });
            } else {
                // If company admin has no organizations, fall back to their own consultations
                $query = Consultation::forUser($user->id);
            }
        } else {
            $query = Consultation::forUser($user->id);
        }

        // Use single query with groupBy for better performance
        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statistics = [
            'total' => array_sum($statusCounts),
            'pending' => $statusCounts[Consultation::STATUS_PENDING] ?? 0,
            'in_progress' => $statusCounts[Consultation::STATUS_IN_PROGRESS] ?? 0,
            'archived' => $statusCounts[Consultation::STATUS_ARCHIVED] ?? 0,
            'cancelled' => $statusCounts[Consultation::STATUS_CANCELLED] ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Client withdraws consultation (only in pending status).
     *
     * @param WithdrawConsultationRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdraw(WithdrawConsultationRequest $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);

            $this->consultationService->clientWithdraw(
                $consultation,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.withdrawn'),
                'data' => $consultation->fresh(['statusLogs']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Client escalates priority.
     *
     * @param EscalatePriorityRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function escalatePriority(EscalatePriorityRequest $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);

            $this->consultationService->escalatePriority(
                $consultation,
                $request->user(),
                $request->priority,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.priority_escalated'),
                'data' => $consultation->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Client archives consultation.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function archive(Request $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);

            $this->consultationService->archiveConsultation(
                $consultation,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.archived'),
                'data' => $consultation->fresh(['statusLogs']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Client unarchives consultation (restore to in_progress).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unarchive(Request $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);

            $this->consultationService->unarchiveConsultation(
                $consultation,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => __('api.consultation.unarchived'),
                'data' => $consultation->fresh(['statusLogs']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

