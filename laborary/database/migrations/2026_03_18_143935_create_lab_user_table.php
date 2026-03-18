<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
           $table->string('account')->unique()->comment('登录账号');
            $table->string('username')->comment('姓名');
            $table->string('phone')->nullable()->unique()->comment('电话');
           $table->string('email')->unique()->comment('邮箱');
            $table->string('password_hash', 255)->comment('加密后的密码');
            $table->tinyInteger('is_active')->default(0)->comment('是否已激活(0:未激活，1:已激活)');
            $table->tinyInteger('role')->default(2)->comment('角色（1=管理员，2=员工）');
            $table->unsignedBigInteger('department_id')->nullable()->comment('所属部门id');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();
            
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};