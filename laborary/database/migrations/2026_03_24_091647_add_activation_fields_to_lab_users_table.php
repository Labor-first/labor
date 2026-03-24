<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lab_users', function (Blueprint $table) {
            $table->string('activation_code')->nullable(); // 激活码
            $table->timestamp('activation_expire')->nullable(); // 激活码过期时间
        });
    }

    public function down()
    {
        Schema::table('lab_users', function (Blueprint $table) {
            $table->dropColumn(['activation_code', 'activation_expire']);
        });
    }
};