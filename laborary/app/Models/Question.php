<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions';

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') : null;
    }

    public function getAnsweredAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') : null;
    }

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
        'answer',
        'answered_by',
        'answered_at',
    ];

    protected $casts = [
        'status' => 'string',
        'answered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(LabUser::class, 'user_id');
    }

    public function answerer(): BelongsTo
    {
        return $this->belongsTo(LabUser::class, 'answered_by');
    }

    /**
     * 检查是否是待回复状态
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * 标记为已回复
     */
    public function markAsAnswered(int $adminId, string $answerContent): void
    {
        $this->update([
            'status' => 'answered',
            'answer' => $answerContent,
            'answered_by' => $adminId,
            'answered_at' => now(),
        ]);
    }

    /**
     * 标记为已解决
     */
    public function markAsResolved(): void
    {
        $this->update(['status' => 'resolved']);
    }
}