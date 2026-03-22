<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 执行迁移：给lab_users表添加激活码字段
     */
    public function up(): void
    {
        Schema::table('lab_users',function(Blueprint $table){
            //激活码（字符串）
            $table->string('activation_code')->nullable()->comment('账号激活码');
            //激活码过期时间（时间戳）
            $table->timestamp('avtivation_expire')->nullable()->comment('激活码过期时间');
        });
    }

    /**
     * 回滚迁移：删除新增的激活码字段
     */
    public function down(): void
    {
        Schema::table('lab_users', function (Blueprint $table) {
            //回滚是删除这两个字段（和up()反向）
            $table->dropColumn(['activation_code','activation_expire']);
        });
    }
};
