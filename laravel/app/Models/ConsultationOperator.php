<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationOperator extends Model
{
    protected $fillable = [
        'consultation_id',
        'operator_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}


