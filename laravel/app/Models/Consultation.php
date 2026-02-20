<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultation extends Model
{
    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_CANCELLED = 'cancelled';

    const TOPIC_LEGAL_CONSULTATION = 'legal_consultation';
    const TOPIC_CONTRACTS_DEALS = 'contracts_deals';
    const TOPIC_LEGAL_SERVICES = 'legal_services';
    const TOPIC_OTHER = 'other';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'title',
        'description',
        'topic_type',
        'status',
        'priority',
        'created_by',
        'assigned_lawyer_id',
        'chat_id',
        'reference_number',
        'resolution',
        'completion_notes',
        'priority_escalated_at',
        'assigned_at',
        'started_at',
        'completed_at',
        'delivered_at',
        'lawyer_delivered_at',
        'client_confirmed_at',
        'archived_at',
        'archived_by',
        'last_activity_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'lawyer_delivered_at' => 'datetime',
        'client_confirmed_at' => 'datetime',
        'archived_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'priority_escalated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the creator of the consultation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the assigned lawyer.
     */
    public function assignedLawyer()
    {
        return $this->belongsTo(User::class, 'assigned_lawyer_id');
    }

    /**
     * Get all assigned lawyers (many-to-many).
     */
    public function lawyers()
    {
        return $this->belongsToMany(User::class, 'consultation_lawyers', 'consultation_id', 'lawyer_id')
            ->withPivot('is_primary', 'assigned_by', 'assigned_at')
            ->withTimestamps()
            ->orderByPivot('is_primary', 'desc')
            ->orderByPivot('assigned_at', 'asc');
    }

    /**
     * Get assigned operators.
     */
    public function operators()
    {
        return $this->belongsToMany(User::class, 'consultation_operators', 'consultation_id', 'operator_id')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps()
            ->orderByPivot('assigned_at', 'asc');
    }

    /**
     * Get the primary lawyer.
     */
    public function primaryLawyer()
    {
        return $this->belongsToMany(User::class, 'consultation_lawyers', 'consultation_id', 'lawyer_id')
            ->wherePivot('is_primary', true)
            ->withPivot('is_primary', 'assigned_by', 'assigned_at')
            ->withTimestamps()
            ->limit(1);
    }

    /**
     * Get the user who cancelled this consultation.
     */
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the user who archived this consultation.
     */
    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Get the associated chat room.
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * Get the files attached to this consultation.
     */
    public function files()
    {
        return $this->hasMany(ConsultationFile::class, 'consultation_id');
    }

    /**
     * Get only deliverable files.
     */
    public function deliverables()
    {
        return $this->hasMany(ConsultationFile::class, 'consultation_id')
            ->where('is_deliverable', true);
    }

    /**
     * Get delivered files (accessible by client).
     */
    public function deliveredFiles()
    {
        return $this->hasMany(ConsultationFile::class, 'consultation_id')
            ->where('is_deliverable', true)
            ->where('can_client_access', true)
            ->whereNotNull('delivered_at');
    }

    /**
     * Get the latest version of files (root files only).
     */
    public function latestFiles()
    {
        return $this->files()->whereNull('parent_file_id');
    }

    /**
     * Get the status change logs.
     */
    public function statusLogs()
    {
        return $this->hasMany(ConsultationStatusLog::class, 'consultation_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by topic type.
     */
    public function scopeByTopic($query, $topic)
    {
        return $query->where('topic_type', $topic);
    }

    /**
     * Scope: Get consultations for a specific user (creator).
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Get consultations assigned to a specific lawyer.
     */
    public function scopeForLawyer($query, $lawyerId)
    {
        return $query->where('assigned_lawyer_id', $lawyerId)
            ->orWhereHas('lawyers', function ($q) use ($lawyerId) {
                $q->where('lawyer_id', $lawyerId);
            });
    }

    /**
     * Scope: Get consultations for a specific company (by company_id).
     * Returns consultations created by users who belong to organizations with the given company_id.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->whereHas('creator.organizations', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }

    /**
     * Check if a user can access this consultation.
     */
    public function canBeAccessedBy(User $user)
    {
        // Creator can access
        if ($this->created_by === $user->id) {
            return true;
        }

        // Company admin can access consultations from their company
        if ($user->isCompanyAdmin()) {
            // Get company_ids that the user belongs to
            $userCompanyIds = $user->organizations()->pluck('company_id')->toArray();
            
            // Check if the consultation creator belongs to any of these companies
            if (!empty($userCompanyIds) && $this->relationLoaded('creator')) {
                // Load organizations if not already loaded
                if (!$this->creator->relationLoaded('organizations')) {
                    $this->creator->load('organizations');
                }
                $creatorCompanyIds = $this->creator->organizations->pluck('company_id')->toArray();
                if (!empty(array_intersect($userCompanyIds, $creatorCompanyIds))) {
                    return true;
                }
            } elseif (!empty($userCompanyIds)) {
                // If creator is not loaded, use a query to check
                $hasCommonCompany = $this->creator()
                    ->whereHas('organizations', function ($q) use ($userCompanyIds) {
                        $q->whereIn('company_id', $userCompanyIds);
                    })
                    ->exists();
                if ($hasCommonCompany) {
                    return true;
                }
            }
        }

        // Assigned lawyer (legacy) can access
        if ($this->assigned_lawyer_id === $user->id) {
            return true;
        }

        // Check if user is in assigned lawyers
        if ($this->lawyers()->where('lawyer_id', $user->id)->exists()) {
            return true;
        }

        // Assigned operators can access
        if ($this->operators()->where('operator_id', $user->id)->exists()) {
            return true;
        }

        // Admin users can access (handled in admin guard)

        return false;
    }

    /**
     * Check if consultation is pending.
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if consultation is in progress.
     */
    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if consultation is archived.
     */
    public function isArchived()
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Check if consultation is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if consultation can be withdrawn by client.
     */
    public function canBeWithdrawn()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if consultation can be archived by client.
     */
    public function canBeArchived()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if consultation can be unarchived (restored to in_progress).
     */
    public function canBeUnarchived()
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Check if priority can be escalated.
     */
    public function canEscalatePriority()
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS
        ]);
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get all available topics.
     */
    public static function getTopics()
    {
        return [
            self::TOPIC_LEGAL_CONSULTATION => 'Legal Consultation',
            self::TOPIC_CONTRACTS_DEALS => 'Contracts & Deals',
            self::TOPIC_LEGAL_SERVICES => 'Legal Services',
            self::TOPIC_OTHER => 'Other',
        ];
    }

    /**
     * Get all available priorities.
     */
    public static function getPriorities()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }
}

