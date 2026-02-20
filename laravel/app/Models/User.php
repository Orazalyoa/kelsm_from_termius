<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    const TYPE_COMPANY_ADMIN = 'company_admin';
    const TYPE_EXPERT = 'expert';
    const TYPE_LAWYER = 'lawyer';
    const TYPE_OPERATOR = 'operator';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_type', 'permissions', 'email', 'phone', 'country_code', 'password',
        'first_name', 'last_name', 'gender', 'avatar', 'locale', 'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'permissions' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['avatar_url', 'full_name'];

    /**
     * Get the avatar URL attribute.
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    /**
     * Get the full name attribute.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if user is company admin.
     *
     * @return bool
     */
    public function isCompanyAdmin()
    {
        return $this->user_type === self::TYPE_COMPANY_ADMIN;
    }

    /**
     * Check if user is expert.
     *
     * @return bool
     */
    public function isExpert()
    {
        return $this->user_type === self::TYPE_EXPERT;
    }

    /**
     * Check if user is lawyer.
     *
     * @return bool
     */
    public function isLawyer()
    {
        return $this->user_type === self::TYPE_LAWYER;
    }

    /**
     * Check if user is operator.
     *
     * @return bool
     */
    public function isOperator()
    {
        return $this->user_type === self::TYPE_OPERATOR;
    }

    /**
     * Check if user can create consultations.
     *
     * @return bool
     */
    public function canCreateConsultations()
    {
        // Lawyers cannot create consultations
        if ($this->isLawyer()) {
            return false;
        }
        
        // Company admin has full permissions (same as owner)
        if ($this->isCompanyAdmin()) {
            return true;
        }
        
        // Check if user is owner in any organization
        $isOwner = $this->organizations()
            ->wherePivot('role', 'owner')
            ->exists();
        
        if ($isOwner) {
            return true;
        }
        
        // For experts, check can_apply_consultation permission
        $permissions = $this->permissions ?? [];
        return $permissions['can_apply_consultation'] ?? false;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_type' => $this->user_type,
        ];
    }

    /**
     * Get the organizations that the user belongs to.
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->withPivot('role', 'permissions', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the professions that the user has.
     */
    public function professions()
    {
        return $this->belongsToMany(Profession::class, 'user_professions')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the invite codes created by the user.
     */
    public function createdInviteCodes()
    {
        return $this->hasMany(InviteCode::class, 'created_by');
    }

    /**
     * Get the invite code uses by the user.
     */
    public function inviteCodeUses()
    {
        return $this->hasMany(InviteCodeUse::class);
    }

    /**
     * Scope a query to only include company admins.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompanyAdmins($query)
    {
        return $query->where('user_type', self::TYPE_COMPANY_ADMIN);
    }

    /**
     * Scope a query to only include experts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExperts($query)
    {
        return $query->where('user_type', self::TYPE_EXPERT);
    }

    /**
     * Scope a query to only include lawyers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLawyers($query)
    {
        return $query->where('user_type', self::TYPE_LAWYER);
    }

    /**
     * Scope a query to only include operators.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOperators($query)
    {
        return $query->where('user_type', self::TYPE_OPERATOR);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get the chats that the user participates in.
     */
    public function chats()
    {
        return $this->belongsToMany(Chat::class, 'chat_participants', 'user_id', 'chat_id')
            ->withPivot('role', 'joined_at', 'last_read_at')
            ->withTimestamps();
    }

    /**
     * Get the messages sent by the user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get the chats created by the user.
     */
    public function createdChats()
    {
        return $this->hasMany(Chat::class, 'created_by');
    }

    /**
     * Get the files uploaded by the user.
     */
    public function uploadedFiles()
    {
        return $this->hasMany(ChatFile::class, 'uploaded_by');
    }

    /**
     * Get the consultations created by the user.
     */
    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'created_by');
    }

    /**
     * Get the consultations assigned to the user (for lawyers).
     */
    public function assignedConsultations()
    {
        return $this->hasMany(Consultation::class, 'assigned_lawyer_id');
    }

    /**
     * Get the consultation files uploaded by the user.
     */
    public function uploadedConsultationFiles()
    {
        return $this->hasMany(ConsultationFile::class, 'uploaded_by');
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
