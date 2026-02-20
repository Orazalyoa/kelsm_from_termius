<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationLawyer extends Model
{
    protected $fillable = [
        'consultation_id',
        'lawyer_id',
        'is_primary',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the consultation.
     */
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the lawyer.
     */
    public function lawyer()
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    /**
     * Get the user who assigned this lawyer.
     */
    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

