<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class LabUser extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;
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
        'activation_code',// 激活码
        'activation_expire',// 激活码过期时间
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'integer',// 转数字
        'role' => 'integer',// 转数字
        'last_login_at' => 'datetime',// 转时间
        'activation_expire' => 'datetime', // 激活码过期时间转时间类型
    ];

    //新增密码验证方法
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    //新增激活码验证方法
    public function isActivationCodeValid(string $code):bool
    {
        //条件：激活码匹配+未过期+账号未激活
        return $this->actiation_code === $code
            && !is_null($this->activation_expire)
            && $this->activation_expire->isFuture()
            && $this->is_active == 0;
    }

    //新增清空激活码方法
    public function clearActivationCode()
    {
        $this->activation_code = null;
        $this->activation_expire = null;
        $this->save();
    }

    //新增标记账号激活方法
    public function markAsActivated()
    {
        $this->is_active = 1;
        $this->clearActivationCode();
        $this->save();
    }

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
