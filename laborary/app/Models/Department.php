<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',//部门名称
        'intro',//部门介绍
        'tech_stack',//技术栈
    ];

    public function labUsers(): HasMany
    {
        return $this->hasMany(LabUser::class);
    }

    public function registrationConfigs(): HasMany
    {
        return $this->hasMany(RegistrationConfig::class);
    }
}