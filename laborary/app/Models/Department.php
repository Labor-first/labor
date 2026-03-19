<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'intro',
        'tech_stack',
        'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function labUsers(): HasMany
    {
        return $this->hasMany(LabUser::class);
    }

    public function labConfigs(): HasMany
    {
        return $this->hasMany(LabConfig::class);
    }

    public function registrationConfigs(): HasMany
    {
        return $this->hasMany(RegistrationConfig::class);
    }
}