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
        'title',//报名表标题
        'reg_start_time',//报名开始时间
        'reg_end_time',//报名截止时间
        'department_id',//关联部门ID
        'is_open',//是否开启报名
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