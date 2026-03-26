<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class FormDraft extends Model
{
    use HasFactory;

    protected $table = 'form_drafts';

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

    public function getExpiresAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') : null;
    }

    protected $fillable = [
        'user_id',
        'form_type',
        'config_id',
        'form_data',
        'current_step',
        'total_steps',
        'expires_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(LabUser::class, 'user_id');
    }

    /**
     * 检查草稿是否已过期
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * 获取草稿进度百分比
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_steps <= 0) {
            return 0;
        }
        return min(100, (int) (($this->current_step / $this->total_steps) * 100));
    }
}