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
        'name',
        'intro',
        'address',
        'contact',
        'department_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}