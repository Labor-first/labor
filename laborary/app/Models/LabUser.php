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
        'account',//账号
        'username',//用户名
        'phone',//手机号
        'email',//邮箱
        'password_hash',//密码哈希值
        'is_active',//是否激活
        'role',//角色
        'department_id',//关联部门ID
        'last_login_at',//最后登录时间
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'integer',// 转数字
        'role' => 'integer',// 转数字
        'last_login_at' => 'datetime',// 转时间
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(\app\Models\LabNews::class, 'author_id');
    }
    // 用户的报名记录【一对一！只能有一个！】
    public function applicationForm(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ApplicationForm::class, 'user_id');
    }
}
