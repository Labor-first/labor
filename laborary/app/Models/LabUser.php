<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'lab_users';

    protected $fillable = [
        'account',
        'username',
        'phone',
        'email',
        'password_hash',
        'is_active',
        'role',
        'department_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'integer',
        'role' => 'integer',
        'last_login_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(\App\Models\LabNews::class, 'author_id');
    }

    public function activityRegistrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class);
    }
}