<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TrainingNotification extends Model
{
    use HasFactory;
    protected $table = 'training_notifications';
    protected $fillable = [
        'title',
        'content',
        'target',
        'target_ids',
        'send_time_type',
        'scheduled_time',
        'is_draft',
        'status',
        'created_id',
        'sent_at',
    ];
    protected $casts = [
        'target_ids' => 'array',
        'is_draft' => 'boolean',
        'scheduled_time' => 'datetime',
        'sent_at' => 'datetime',
    ];

    //可以将"sent_at": "2026-03-26T08:00:00.000000Z"变成"sent_at": "2026-03-26 16:00:00"
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }
}
