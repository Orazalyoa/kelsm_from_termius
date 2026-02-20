<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationFile extends Model
{
    protected $fillable = [
        'consultation_id',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'file_category',
        'is_deliverable',
        'delivered_at',
        'can_client_access',
        'version',
        'parent_file_id',
        'uploaded_by',
        'version_notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'is_deliverable' => 'boolean',
        'can_client_access' => 'boolean',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the consultation this file belongs to.
     */
    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    /**
     * Get the user who uploaded this file.
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the parent file (for versioning).
     */
    public function parentFile()
    {
        return $this->belongsTo(ConsultationFile::class, 'parent_file_id');
    }

    /**
     * Get all versions of this file (children).
     */
    public function versions()
    {
        return $this->hasMany(ConsultationFile::class, 'parent_file_id');
    }

    /**
     * Get the latest version of this file.
     */
    public function getLatestVersion()
    {
        if (!$this->parent_file_id) {
            // This is the original file, check if it has versions
            return $this->versions()
                ->orderBy('version', 'desc')
                ->first() ?? $this;
        } else {
            // This is a version, get the parent and then the latest
            return $this->parentFile->getLatestVersion();
        }
    }

    /**
     * Get all versions including this one (ordered by version).
     */
    public function getAllVersions()
    {
        if ($this->parent_file_id) {
            // If this is a version, start from parent
            return $this->parentFile->getAllVersions();
        }

        // This is the original, get all versions
        return collect([$this])->merge(
            $this->versions()->orderBy('version', 'asc')->get()
        );
    }

    /**
     * Get the file URL.
     */
    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get formatted file size.
     */
    public function getFileSizeFormattedAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Scope: Get root files only (no versions).
     */
    public function scopeRootFiles($query)
    {
        return $query->whereNull('parent_file_id');
    }

    /**
     * Scope: Get files by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope: Get deliverable files only.
     */
    public function scopeDeliverables($query)
    {
        return $query->where('is_deliverable', true);
    }

    /**
     * Scope: Get delivered files (accessible by client).
     */
    public function scopeDelivered($query)
    {
        return $query->where('is_deliverable', true)
            ->where('can_client_access', true)
            ->whereNotNull('delivered_at');
    }

    /**
     * Check if file is accessible by client.
     */
    public function isAccessibleByClient()
    {
        if (!$this->is_deliverable) {
            return true; // 非交付物默认可访问
        }
        
        return $this->can_client_access && !is_null($this->delivered_at);
    }
}


