<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class HomeworkTask extends Model
{
    use HasFactory;

    protected $table = 'homework_tasks';

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }

    protected $fillable = [
        'title',
        'content',
        'attachment',
        'deadline',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    /**
     * 发布人（管理员）
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(LabUser::class, 'created_by');
    }

    /**
     * 学员提交记录
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'task_id');
    }

    /**
     * 是否已截止
     */
    public function isExpired(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }
}