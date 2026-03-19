<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRegistration extends Model
{
    use HasFactory;

    protected $table = 'activity_registration';

    protected $fillable = [
        'config_id',
        'user_id',
        'status',
        'audit_time',
        'audit_remark',
        'class',
        'academy',
        'major',
        'director_name',
        'sign_reason',
    ];

    protected $casts = [
        'status' => 'integer',
        'audit_time' => 'datetime',
        'class' => 'integer',
    ];

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_REJECTED = 4;

    public function config(): BelongsTo
    {
        return $this->belongsTo(RegistrationConfig::class, 'config_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(LabUser::class);
    }
}