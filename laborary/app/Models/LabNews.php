<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabNews extends Model
{
    use HasFactory;

    protected $table = 'lab_news';

    protected $fillable = [
        'title',//新闻标题
        'content',//新闻内容
        'cover',//新闻封面
        'is_top',//是否置顶
        'author_id',//作者ID
        'published_at',//发布时间时间
    ];

    protected $casts = [
        'is_top' => 'integer',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(LabUser::class, 'author_id');
    }
}