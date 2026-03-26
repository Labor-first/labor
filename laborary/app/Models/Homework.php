<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'attachment',
        'score',
        'comment',
        'status',
        'week',
    ];

    // 关联你的学员表（lab_users）
    public function user()
    {
        return $this->belongsTo(LabUser::class);
    }
}