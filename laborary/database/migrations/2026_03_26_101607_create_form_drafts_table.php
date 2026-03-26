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
        Schema::create('form_drafts', function (Blueprint $table) {
            $table->id()->comment('草稿ID，主键');
            $table->string('device_id', 255)->comment('设备标识ID，用于未登录用户识别');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID，登录用户可选填');
            $table->string('form_type', 50)->comment('表单类型，如：registration, application等');
            $table->unsignedBigInteger('config_id')->nullable()->comment('关联的配置ID，如报名表配置ID');
            $table->json('form_data')->comment('表单数据，JSON格式存储');
            $table->integer('current_step')->default(1)->comment('当前填写步骤');
            $table->integer('total_steps')->default(1)->comment('总步骤数');
            $table->timestamp('expires_at')->nullable()->comment('草稿过期时间');
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('user_id');
            $table->index('form_type');
            $table->index(['device_id', 'form_type']);
            $table->index(['user_id', 'form_type']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_drafts');
    }
};
