<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabConfig extends Model
{
    use HasFactory;

    protected $table = 'lab_config';

    protected $fillable = [
        'name',//实验室名称
        'intro',//实验室介绍
        'address',//实验室地址
        'contact',//实验室联系人
        'department_id',//关联部门ID
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}