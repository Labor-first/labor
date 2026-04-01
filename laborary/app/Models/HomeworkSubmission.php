<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class HomeworkSubmission extends Model
{
    use HasFactory;

    protected $table = 'homework_submissions';

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'attachment',
        'status',
        'score',
        'comment',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * 关联作业任务
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(HomeworkTask::class, 'task_id');
    }

    /**
     * 提交学员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(LabUser::class, 'user_id');
    }

    /**
     * 是否已批改
     */
    public function isCorrected(): bool
    {
        return $this->status === 'corrected';
    }

    /**
     * 批改作业
     */
    public function correct(int $score, ?string $comment = null): void
    {
        $this->update([
            'score' => $score,
            'comment' => $comment,
            'status' => 'corrected',
        ]);
    }
}