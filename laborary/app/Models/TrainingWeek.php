<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TrainingWeek extends Model
{
    use HasFactory;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }

    protected $table = 'training_week';

    protected $fillable = [
        'week_name',
        'start_date',
        'end_date',
        'description',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(\App\Models\TrainingWeek::class, 'week_id');
    }

    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'week_id');
    }
}