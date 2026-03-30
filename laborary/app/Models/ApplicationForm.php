<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ApplicationForm extends Model
{
    use HasFactory;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }

    protected $table = 'application_forms';

    protected $fillable = [
        'config_id',//报名表配置ID
        'user_id',//('报名人ID,x学号');
        'name',//姓名
        'status',//报名状态
        'audit_time',//审核时间
        'audit_remark',//审核备注/拒绝原因
        'class',//班级
        'academy',//学院
        'major',//专业
        'email',
        'director_name',//导员姓名
        'sign_reason',//报名理由
    ];

    protected $casts = [
        'status' => 'integer',
        'audit_time' => 'datetime',
        'class' => 'string',
    ];

    const STATUS_PENDING = 1;   // 待审核
    const STATUS_APPROVED = 2;  // 已通过
    const STATUS_CANCELLED = 3; // 已取消
    const STATUS_REJECTED = 4;  // 已拒绝

    public function config(): BelongsTo
    {
        return $this->belongsTo(RegistrationConfig::class, 'config_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(LabUser::class);
    }
}
