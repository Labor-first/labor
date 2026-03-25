<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lab_users', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_users', 'activation_expire')) {
                $table->timestamp('activation_expire')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_users', function (Blueprint $table) {
            if (Schema::hasColumn('lab_users', 'activation_expire')) {
                $table->dropColumn('activation_expire');
            }
        });
    }
};