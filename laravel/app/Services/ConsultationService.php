<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\ConsultationFile;
use App\Models\ConsultationStatusLog;
use App\Models\ConsultationLawyer;
use App\Models\ConsultationOperator;
use App\Models\User;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ConsultationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Create a new consultation with optional files.
     *
     * @param User $user
     * @param array $data
     * @param array $files
     * @return Consultation
     */
    public function createConsultation(User $user, array $data, array $files = [])
    {
        // Ensure user can create consultations (not a lawyer)
        if ($user->isLawyer()) {
            throw new \Exception('Lawyers cannot create consultations');
        }
        
        // Check if user has permission to create consultations
        if (!$user->canCreateConsultations()) {
            throw new \Exception(__('request_validation.store_consultation.permission_denied'));
        }

        DB::beginTransaction();
        try {
            // Generate title if not provided
            if (empty($data['title'])) {
                $topicLabels = [
                    'legal_consultation' => '法律咨询',
                    'contracts_deals' => '合约/交易',
                    'legal_services' => '法律服务',
                    'other' => '其他咨询'
                ];
                $topicType = $data['topic_type'] ?? Consultation::TOPIC_LEGAL_CONSULTATION;
                $data['title'] = $topicLabels[$topicType] ?? '咨询申请';
            }

            // Create consultation
            $consultation = Consultation::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'topic_type' => $data['topic_type'] ?? Consultation::TOPIC_LEGAL_CONSULTATION,
                'priority' => $data['priority'] ?? Consultation::PRIORITY_MEDIUM,
                'status' => Consultation::STATUS_PENDING,
                'created_by' => $user->id,
                'last_activity_at' => now(),
            ]);

            // Log initial status
            ConsultationStatusLog::create([
                'consultation_id' => $consultation->id,
                'old_status' => null,
                'new_status' => Consultation::STATUS_PENDING,
                'changed_by' => $user->id,
                'reason' => 'Consultation created',
            ]);

            // Handle file uploads
            if (!empty($files)) {
                $uploadedFiles = [];
                try {
                    foreach ($files as $file) {
                        $uploadedFiles[] = $this->uploadFile($consultation, $file, $user);
                    }
                } catch (\Exception $e) {
                    // If file upload fails, clean up uploaded files
                    foreach ($uploadedFiles as $uploadedFile) {
                        try {
                            $this->deleteFile($uploadedFile);
                        } catch (\Exception $deleteException) {
                            // Log but don't throw
                        }
                    }
                    throw $e;
                }
            }

            DB::commit();
            return $consultation->load(['creator', 'files', 'statusLogs']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign a lawyer to a consultation and create chat room (legacy method).
     *
     * @param Consultation $consultation
     * @param User $lawyer
     * @param User $assignedBy
     * @return Consultation
     */
    public function assignLawyer(Consultation $consultation, User $lawyer, User $assignedBy)
    {
        // Use the new multi-lawyer assignment with single lawyer
        return $this->assignLawyers($consultation, [$lawyer->id], $assignedBy, true);
    }

    /**
     * Assign multiple lawyers to a consultation.
     *
     * @param Consultation $consultation
     * @param array $lawyerIds
     * @param User $assignedBy
     * @param bool $setPrimary Set first lawyer as primary
     * @return Consultation
     */
    public function assignLawyers(Consultation $consultation, array $lawyerIds, User $assignedBy, bool $setPrimary = true)
    {
        if (empty($lawyerIds)) {
            throw new \Exception('至少需要分配一个律师');
        }

        // Validate all lawyers
        $lawyers = User::whereIn('id', $lawyerIds)->get();
        
        if ($lawyers->count() !== count($lawyerIds)) {
            throw new \Exception('部分律师ID无效');
        }

        foreach ($lawyers as $lawyer) {
            if (!$lawyer->isLawyer()) {
                throw new \Exception("用户 {$lawyer->full_name} 不是律师");
            }
        }

        DB::beginTransaction();
        try {
            $now = now();
            $oldStatus = $consultation->status;
            $isFirstAssignment = $consultation->isPending();
            $chat = $this->ensureChatRoom($consultation, $assignedBy);

            // Get existing lawyer IDs
            $existingLawyerIds = $consultation->lawyers()->pluck('lawyer_id')->toArray();
            $newLawyerIds = array_diff($lawyerIds, $existingLawyerIds);

            // Assign new lawyers
            $newLawyerIndex = 0;
            foreach ($lawyers as $lawyer) {
                // Skip if already assigned
                if (in_array($lawyer->id, $existingLawyerIds)) {
                    continue;
                }

                // Determine if this is primary lawyer (first new lawyer on first assignment)
                $isPrimary = ($setPrimary && $newLawyerIndex === 0 && $isFirstAssignment);
                $newLawyerIndex++;

                // Create assignment record
                ConsultationLawyer::create([
                    'consultation_id' => $consultation->id,
                    'lawyer_id' => $lawyer->id,
                    'is_primary' => $isPrimary,
                    'assigned_by' => $assignedBy->id,
                    'assigned_at' => $now,
                ]);

                // Add lawyer to chat
                if ($consultation->chat_id) {
                    // Check if lawyer is already a participant
                    $existingParticipant = ChatParticipant::where('chat_id', $consultation->chat_id)
                        ->where('user_id', $lawyer->id)
                        ->first();

                    if (!$existingParticipant) {
                        ChatParticipant::create([
                            'chat_id' => $consultation->chat_id,
                            'user_id' => $lawyer->id,
                            'role' => 'lawyer',
                            'joined_at' => $now,
                        ]);

                        // Send system message
                        $this->createSystemMessage(
                            $consultation->chat_id,
                            'lawyer_joined',
                            [
                                'user_id' => $lawyer->id,
                                'name' => $lawyer->full_name,
                                'role' => 'lawyer',
                            ],
                            __('message.lawyer_joined', ['lawyer' => $lawyer->full_name])
                        );
                    }
                }

                // Send notification to lawyer
                $this->notificationService->notifyConsultationAssigned(
                    $lawyer,
                    $consultation->id,
                    $consultation->title
                );
            }

            // Update consultation status if it's first assignment
            if ($isFirstAssignment) {
                $primaryLawyer = $lawyers->first();
                
                $consultation->update([
                    'assigned_lawyer_id' => $primaryLawyer->id,
                    'status' => Consultation::STATUS_IN_PROGRESS,
                    'assigned_at' => $now,
                    'started_at' => $now,
                    'last_activity_at' => $now,
                ]);

                // Log status change
                $lawyerNames = $lawyers->pluck('full_name')->implode(', ');
                ConsultationStatusLog::create([
                    'consultation_id' => $consultation->id,
                    'old_status' => $oldStatus,
                    'new_status' => Consultation::STATUS_IN_PROGRESS,
                    'changed_by' => $assignedBy->id,
                    'reason' => "分配给律师: {$lawyerNames}",
                ]);

                // Send notification to creator
                $this->notificationService->notifyConsultationStatusChange(
                    $consultation->created_by,
                    $consultation->id,
                    $oldStatus,
                    Consultation::STATUS_IN_PROGRESS
                );
            } else {
                // Just log the addition of new lawyers
                if (!empty($newLawyerIds)) {
                    $newLawyers = $lawyers->filter(function ($lawyer) use ($newLawyerIds) {
                        return in_array($lawyer->id, $newLawyerIds);
                    });
                    $lawyerNames = $newLawyers->pluck('full_name')->implode(', ');
                    
                    ConsultationStatusLog::create([
                        'consultation_id' => $consultation->id,
                        'old_status' => $oldStatus,
                        'new_status' => $oldStatus,
                        'changed_by' => $assignedBy->id,
                        'reason' => "添加律师: {$lawyerNames}",
                    ]);

                    $consultation->update([
                        'last_activity_at' => $now,
                    ]);
                }
            }

            DB::commit();
            return $consultation->fresh(['creator', 'assignedLawyer', 'lawyers', 'chat', 'files', 'statusLogs']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign operators to a consultation.
     */
    public function assignOperators(Consultation $consultation, array $operatorIds, User $assignedBy)
    {
        if (empty($operatorIds)) {
            throw new \Exception('至少需要选择一个客服人员');
        }

        $operators = User::whereIn('id', $operatorIds)->get();
        if ($operators->count() !== count($operatorIds)) {
            throw new \Exception('部分客服ID无效');
        }

        foreach ($operators as $operator) {
            if (!$operator->isOperator()) {
                throw new \Exception("用户 {$operator->full_name} 不是客服");
            }
        }

        DB::beginTransaction();
        try {
            $now = now();
            $chat = $this->ensureChatRoom($consultation, $assignedBy);
            $participantAdded = false;

            foreach ($operators as $operator) {
                ConsultationOperator::updateOrCreate(
                    [
                        'consultation_id' => $consultation->id,
                        'operator_id' => $operator->id,
                    ],
                    [
                        'assigned_by' => $assignedBy->id,
                        'assigned_at' => $now,
                    ]
                );

                if ($consultation->chat_id && $chat) {
                    $exists = ChatParticipant::where('chat_id', $consultation->chat_id)
                        ->where('user_id', $operator->id)
                        ->exists();

                    if (!$exists) {
                        ChatParticipant::create([
                            'chat_id' => $consultation->chat_id,
                            'user_id' => $operator->id,
                            'role' => 'operator',
                            'joined_at' => $now,
                        ]);

                        $this->createSystemMessage(
                            $consultation->chat_id,
                            'operator_joined',
                            [
                                'user_id' => $operator->id,
                                'name' => $operator->full_name,
                                'role' => 'operator',
                            ],
                            __('message.operator_joined', ['operator' => $operator->full_name])
                        );

                        $participantAdded = true;
                    }
                }
            }

            if ($participantAdded) {
                $consultation->update([
                    'last_activity_at' => $now,
                ]);
            } else {
                $consultation->touch('updated_at');
            }

            DB::commit();

            return $consultation->fresh(['operators', 'chat']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a lawyer from a consultation.
     *
     * @param Consultation $consultation
     * @param int $lawyerId
     * @param User $removedBy
     * @return Consultation
     */
    public function removeLawyer(Consultation $consultation, int $lawyerId, User $removedBy)
    {
        // Cannot remove if consultation is archived or cancelled
        if (in_array($consultation->status, [Consultation::STATUS_ARCHIVED, Consultation::STATUS_CANCELLED])) {
            throw new \Exception('无法从已归档或已取消的咨询中移除律师');
        }

        DB::beginTransaction();
        try {
            // Find the assignment
            $assignment = ConsultationLawyer::where('consultation_id', $consultation->id)
                ->where('lawyer_id', $lawyerId)
                ->first();

            if (!$assignment) {
                throw new \Exception('该律师未被分配到此咨询');
            }

            // Check if this is the only lawyer
            $lawyerCount = $consultation->lawyers()->count();
            if ($lawyerCount <= 1) {
                throw new \Exception('至少需要保留一个律师');
            }

            $lawyer = User::findOrFail($lawyerId);

            // Remove from consultation_lawyers
            $assignment->delete();

            // Remove from chat participants
            if ($consultation->chat_id) {
                ChatParticipant::where('chat_id', $consultation->chat_id)
                    ->where('user_id', $lawyerId)
                    ->delete();

                // Send system message
                $this->createSystemMessage(
                    $consultation->chat_id,
                    'lawyer_removed',
                    [
                        'user_id' => $lawyer->id,
                        'name' => $lawyer->full_name,
                        'role' => 'lawyer',
                    ],
                    __('message.lawyer_removed', ['lawyer' => $lawyer->full_name])
                );
            }

            // If removed lawyer was the primary, update consultation
            if ($consultation->assigned_lawyer_id == $lawyerId) {
                // Clear old primary flags first
                ConsultationLawyer::where('consultation_id', $consultation->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);

                // Set first remaining lawyer as primary
                $newPrimaryLawyer = $consultation->lawyers()->first();
                if ($newPrimaryLawyer) {
                    $consultation->update([
                        'assigned_lawyer_id' => $newPrimaryLawyer->id,
                    ]);

                    // Update primary flag for new primary lawyer
                    ConsultationLawyer::where('consultation_id', $consultation->id)
                        ->where('lawyer_id', $newPrimaryLawyer->id)
                        ->update(['is_primary' => true]);
                } else {
                    // No lawyers left, clear assigned_lawyer_id
                    $consultation->update([
                        'assigned_lawyer_id' => null,
                    ]);
                }
            }

            // Log the removal
            ConsultationStatusLog::create([
                'consultation_id' => $consultation->id,
                'old_status' => $consultation->status,
                'new_status' => $consultation->status,
                'changed_by' => $removedBy->id,
                'reason' => "移除律师: {$lawyer->full_name}",
            ]);

            $consultation->update([
                'last_activity_at' => now(),
            ]);

            DB::commit();
            return $consultation->fresh(['lawyers', 'assignedLawyer', 'statusLogs']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ensure chat room exists for consultation and returns chat instance.
     */
    protected function ensureChatRoom(Consultation $consultation, User $creator): ?Chat
    {
        if ($consultation->chat_id) {
            if ($consultation->relationLoaded('chat') && $consultation->chat) {
                return $consultation->chat;
            }
            return Chat::find($consultation->chat_id);
        }

        $chat = Chat::create([
            'title' => $consultation->title,
            'type' => 'private',
            'created_by' => $creator->id,
        ]);

        ChatParticipant::create([
            'chat_id' => $chat->id,
            'user_id' => $consultation->created_by,
            'role' => 'client',
            'joined_at' => now(),
        ]);

        $consultation->update([
            'chat_id' => $chat->id,
        ]);

        return $chat;
    }

    /**
     * Update consultation status with logging.
     *
     * @param Consultation $consultation
     * @param string $newStatus
     * @param User $user
     * @param string|null $reason
     * @return Consultation
     */
    public function updateStatus(Consultation $consultation, string $newStatus, User $user, ?string $reason = null)
    {
        // Validate status
        if (!in_array($newStatus, array_keys(Consultation::getStatuses()))) {
            throw new \Exception('Invalid status');
        }

        // Check if status is actually changing
        if ($consultation->status === $newStatus) {
            throw new \Exception('Status is already ' . $newStatus);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $consultation->status;

            // Update consultation
            $consultation->update([
                'status' => $newStatus,
                'archived_at' => ($newStatus === Consultation::STATUS_ARCHIVED) ? now() : null,
                'last_activity_at' => now(),
            ]);

            // Log status change
            ConsultationStatusLog::create([
                'consultation_id' => $consultation->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $user->id,
                'reason' => $reason,
            ]);

            // Send system message to chat if exists
            if ($consultation->chat_id) {
                $statusLabels = [
                    'pending' => __('message.status_pending'),
                    'in_progress' => __('message.status_in_progress'),
                    'archived' => __('message.status_archived'),
                    'cancelled' => __('message.status_cancelled'),
                ];
                
                $this->createSystemMessage(
                    $consultation->chat_id,
                    'consultation_status_changed',
                    [
                        'from' => $oldStatus,
                        'to' => $newStatus,
                    ],
                    __('message.status_changed_to', ['status' => $statusLabels[$newStatus] ?? $newStatus])
                );
            }

            // Send notification to consultation creator
            $this->notificationService->notifyConsultationStatusChange(
                $consultation->created_by,
                $consultation->id,
                $oldStatus,
                $newStatus
            );

            // Send notification to assigned lawyer if exists
            if ($consultation->assigned_lawyer_id && $consultation->assigned_lawyer_id !== $user->id) {
                $this->notificationService->notifyConsultationStatusChange(
                    $consultation->assigned_lawyer_id,
                    $consultation->id,
                    $oldStatus,
                    $newStatus
                );
            }

            DB::commit();
            return $consultation->load(['statusLogs']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload a file to a consultation.
     *
     * @param Consultation $consultation
     * @param UploadedFile $file
     * @param User $user
     * @param string|null $versionNotes
     * @param string $fileCategory
     * @return ConsultationFile
     */
    public function uploadFile(Consultation $consultation, UploadedFile $file, User $user, ?string $versionNotes = null, string $fileCategory = 'attachment')
    {
        // Validate file size
        $maxSize = config('consultation.max_file_size', 536870912); // 512MB default
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size');
        }

        // Validate file type
        $allowedTypes = config('consultation.allowed_file_types', ['doc', 'docx', 'pdf', 'jpg', 'png', 'zip']);
        $extension = strtolower($file->getClientOriginalExtension() ?? '');
        if (empty($extension) || !in_array($extension, $allowedTypes)) {
            throw new \Exception('File type not allowed');
        }

        DB::beginTransaction();
        try {
            // Check if file with same name exists
            $fileName = $file->getClientOriginalName() ?? 'file_' . time();
            $existingFile = $consultation->files()
                ->where('file_name', $fileName)
                ->whereNull('parent_file_id')
                ->first();

            $version = 1;
            $parentFileId = null;

            if ($existingFile) {
                // This is a new version
                $latestVersion = $existingFile->getLatestVersion();
                $version = $latestVersion->version + 1;
                $parentFileId = $existingFile->id;
            }

            // Store file
            $path = $file->store('consultations/' . $consultation->id, 'public');

            // 判断是否为交付物
            $isDeliverable = ($fileCategory === 'deliverable');
            
            // Create file record
            $consultationFile = ConsultationFile::create([
                'consultation_id' => $consultation->id,
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => $file->getSize() ?? 0,
                'file_type' => $extension,
                'file_category' => $fileCategory,
                'is_deliverable' => $isDeliverable,
                'version' => $version,
                'parent_file_id' => $parentFileId,
                'uploaded_by' => $user->id,
                'version_notes' => $versionNotes,
                // 交付物默认不可访问，需要律师提交交付后才可访问
                'can_client_access' => !$isDeliverable,
                'delivered_at' => !$isDeliverable ? now() : null,
            ]);

            // Update last activity
            $consultation->update([
                'last_activity_at' => now(),
            ]);

            DB::commit();
            return $consultationFile->load(['uploadedBy', 'parentFile']);
        } catch (\Exception $e) {
            DB::rollBack();
            // Clean up uploaded file if it exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    /**
     * Upload a new version of an existing file.
     *
     * @param ConsultationFile $existingFile
     * @param UploadedFile $file
     * @param User $user
     * @param string|null $versionNotes
     * @return ConsultationFile
     */
    public function uploadFileVersion(ConsultationFile $existingFile, UploadedFile $file, User $user, ?string $versionNotes = null)
    {
        return $this->uploadFile($existingFile->consultation, $file, $user, $versionNotes);
    }

    /**
     * Delete a consultation file.
     *
     * @param ConsultationFile $file
     * @return bool
     */
    public function deleteFile(ConsultationFile $file)
    {
        DB::beginTransaction();
        try {
            // Delete physical file
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            // Delete record
            $file->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Client withdraws consultation (only in pending status).
     *
     * @param Consultation $consultation
     * @param User $client
     * @param string $reason
     * @return Consultation
     */
    public function clientWithdraw(Consultation $consultation, User $client, string $reason)
    {
        // Validate status
        if (!$consultation->canBeWithdrawn()) {
            throw new \Exception('只能撤回未分配律师的咨询');
        }

        // Validate permission
        if ($consultation->created_by !== $client->id) {
            throw new \Exception('只有咨询创建者可以撤回');
        }

        DB::beginTransaction();
        try {
            $consultation->update([
                'status' => Consultation::STATUS_CANCELLED,
                'cancelled_by' => $client->id,
                'cancellation_reason' => $reason,
                'last_activity_at' => now(),
            ]);

            // Log status change
            ConsultationStatusLog::create([
                'consultation_id' => $consultation->id,
                'old_status' => Consultation::STATUS_PENDING,
                'new_status' => Consultation::STATUS_CANCELLED,
                'changed_by' => $client->id,
                'reason' => "客户撤回: {$reason}",
            ]);

            DB::commit();
            return $consultation->fresh(['statusLogs']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Client escalates priority (can only increase, not decrease).
     *
     * @param Consultation $consultation
     * @param User $client
     * @param string $newPriority
     * @param string $reason
     * @return Consultation
     */
    public function escalatePriority(Consultation $consultation, User $client, string $newPriority, string $reason)
    {
        // Validate permission
        if ($consultation->created_by !== $client->id) {
            throw new \Exception('只有咨询创建者可以提升优先级');
        }

        // Validate can escalate
        if (!$consultation->canEscalatePriority()) {
            throw new \Exception('当前状态不允许提升优先级');
        }

        // Validate priority levels
        $priorityLevels = [
            Consultation::PRIORITY_LOW => 1,
            Consultation::PRIORITY_MEDIUM => 2,
            Consultation::PRIORITY_HIGH => 3,
            Consultation::PRIORITY_URGENT => 4,
        ];

        $currentLevel = $priorityLevels[$consultation->priority] ?? 0;
        $newLevel = $priorityLevels[$newPriority] ?? 0;

        if ($newLevel <= $currentLevel) {
            throw new \Exception('只能提升优先级，不能降低');
        }

        DB::beginTransaction();
        try {
            $oldPriority = $consultation->priority;

            $consultation->update([
                'priority' => $newPriority,
                'priority_escalated_at' => now(),
                'last_activity_at' => now(),
            ]);

            // Log priority change
            ConsultationStatusLog::create([
                'consultation_id' => $consultation->id,
                'old_status' => $consultation->status,
                'new_status' => $consultation->status,
                'changed_by' => $client->id,
                'reason' => "优先级从 {$oldPriority} 提升至 {$newPriority}: {$reason}",
            ]);

            // Notify lawyer if assigned
            if ($consultation->assigned_lawyer_id) {
                $this->notificationService->notifyPriorityEscalated(
                    $consultation->assigned_lawyer_id,
                    $consultation->id,
                    $oldPriority,
                    $newPriority
                );
            }

            DB::commit();
            return $consultation->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Client archives consultation (only from in_progress status).
     *
     * @param Consultation $consultation
     * @param User $client
     * @return Consultation
     */
    public function archiveConsultation(Consultation $consultation, User $client)
    {
        // Validate permission
        if ($consultation->created_by !== $client->id) {
            throw new \Exception('只有咨询创建者可以归档');
        }

        // Validate status
        if (!$consultation->canBeArchived()) {
            throw new \Exception('只能归档进行中的咨询');
        }

        DB::beginTransaction();
        try {
            $now = now();

            // Update consultation status to archived
            $consultation->update([
                'status' => Consultation::STATUS_ARCHIVED,
                'archived_at' => $now,
                'archived_by' => $client->id,
                'last_activity_at' => $now,
            ]);

            // Log status change
            ConsultationStatusLog::create([
                'consultation_id' => $consultation->id,
                'old_status' => Consultation::STATUS_IN_PROGRESS,
                'new_status' => Consultation::STATUS_ARCHIVED,
                'changed_by' => $client->id,
                'reason' => '客户归档咨询',
            ]);

            // Set chat as read-only (archived)
            if ($consultation->chat_id) {
                $chat = Chat::find($consultation->chat_id);
                if ($chat) {
                    $chat->setInactive();

                    // Send system message
                    $this->createSystemMessage(
                        $consultation->chat_id,
                        'consultation_archived',
                        [
                            'by' => $client->id,
                        ],
                        __('message.consultation_archived')
                    );
                }
            }

            // Notify assigned lawyer if exists
            if ($consultation->assigned_lawyer_id) {
                $this->notificationService->notifyConsultationStatusChange(
                    $consultation->assigned_lawyer_id,
                    $consultation->id,
                    Consultation::STATUS_IN_PROGRESS,
                    Consultation::STATUS_ARCHIVED
                );
            }

            DB::commit();
            return $consultation->fresh(['statusLogs']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Client unarchives consultation (restore from archived to in_progress).
     *
     * @param Consultation $consultation
     * @param User $client
     * @return Consultation
     */
    public function unarchiveConsultation(Consultation $consultation, User $client)
    {
        // Validate permission
        if ($consultation->created_by !== $client->id) {
            throw new \Exception('只有咨询创建者可以恢复');
        }

        // Validate status
        if (!$consultation->canBeUnarchived()) {
            throw new \Exception('只能恢复已归档的咨询');
        }

        DB::beginTransaction();
        try {
            $now = now();

            // Update consultation status back to in_progress
            $consultation->update([
                'status' => Consultation::STATUS_IN_PROGRESS,
                'last_activity_at' => $now,
            ]);

            // Log status change
            ConsultationStatusLog::create([
                'consultation_id' => $consultation->id,
                'old_status' => Consultation::STATUS_ARCHIVED,
                'new_status' => Consultation::STATUS_IN_PROGRESS,
                'changed_by' => $client->id,
                'reason' => '客户恢复咨询',
            ]);

            // Reopen chat if exists
            if ($consultation->chat_id) {
                $chat = Chat::find($consultation->chat_id);
                if ($chat) {
                    $chat->setActive();

                    // Send system message
                    $this->createSystemMessage(
                        $consultation->chat_id,
                        'consultation_unarchived',
                        [
                            'by' => $client->id,
                        ],
                        __('message.consultation_unarchived')
                    );
                }
            }

            // Notify assigned lawyer if exists
            if ($consultation->assigned_lawyer_id) {
                $this->notificationService->notifyConsultationStatusChange(
                    $consultation->assigned_lawyer_id,
                    $consultation->id,
                    Consultation::STATUS_ARCHIVED,
                    Consultation::STATUS_IN_PROGRESS
                );
            }

            DB::commit();
            return $consultation->fresh(['statusLogs']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create structured system message content.
     */
    protected function buildSystemMessageContent(string $type, array $data = [], ?string $message = null): string
    {
        $payload = [
            'type' => $type,
            'data' => $data,
        ];

        if ($message) {
            $payload['message'] = $message;
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Helper to persist a system message with structured payload.
     */
    protected function createSystemMessage(int $chatId, string $type, array $data = [], ?string $message = null): void
    {
        if (!$chatId) {
            return;
        }

        Message::create([
            'chat_id' => $chatId,
            'sender_id' => null,
            'type' => 'system',
            'content' => $this->buildSystemMessageContent($type, $data, $message),
        ]);
    }
}

