<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationStatusLog extends Model
{
    protected $fillable = [
        'consultation_id',
        'old_status',
        'new_status',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the consultation this log belongs to.
     */
    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    /**
     * Get the user who changed the status.
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get formatted status change description.
     */
    public function getDescriptionAttribute()
    {
        $from = $this->old_status ? ucwords(str_replace('_', ' ', $this->old_status)) : 'New';
        $to = ucwords(str_replace('_', ' ', $this->new_status));
        
        return "Status changed from {$from} to {$to}";
    }
}

