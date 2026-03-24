<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lab_users', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_users', 'activation_code')) {
                $table->string('activation_code')->nullable(); // 激活码
            }
            if (!Schema::hasColumn('lab_users', 'activation_expire')) {
                $table->timestamp('activation_expire')->nullable(); // 激活码过期时间
            }
        });
    }

    public function down()
    {
        Schema::table('lab_users', function (Blueprint $table) {
            $table->dropColumn(['activation_code', 'activation_expire']);
        });
    }
};