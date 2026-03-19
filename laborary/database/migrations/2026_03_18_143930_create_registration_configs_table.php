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
        Schema::create('registration_configs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->comment('名称');
            $table->timestamp('reg_start_time')->comment('报名开始时间');
            $table->timestamp('reg_end_time')->comment('报名截止时间');
            $table->unsignedBigInteger('department_id')->comment('关联部门ID');
            $table->tinyInteger('is_open')->default(0)->comment('报名是否开启(0:关闭,1:开启)');
            $table->timestamps();
            
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_configs');
    }
};
