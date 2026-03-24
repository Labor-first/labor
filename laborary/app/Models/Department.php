<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Department extends Model
{
    use HasFactory;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }

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