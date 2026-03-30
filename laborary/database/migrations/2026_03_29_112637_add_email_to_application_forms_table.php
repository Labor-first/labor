<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('application_forms', function (Blueprint $table) {
            // 先检查列是否存在
            if (!Schema::hasColumn('application_forms', 'email')) {
                $table->string('email')->after('major')->comment('邮箱');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_forms', function (Blueprint $table) {
            //
        });
    }
};
