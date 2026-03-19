<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'reg_start_time',
        'reg_end_time',
        'department_id',
        'is_open',
    ];

    protected $casts = [
        'reg_start_time' => 'datetime',
        'reg_end_time' => 'datetime',
        'is_open' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function activityRegistrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class, 'config_id');
    }
}