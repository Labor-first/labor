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
        Schema::create('training_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('通知标题');
            $table->text('content')->comment('通知内容');
            $table->enum('target', ['all', 'week', 'trainee'])->comment('发送对象：all全部/week指定周次/trainee指定学员');
            $table->json('target_ids')->nullable()->comment('目标ID列表（周次ID或学员ID）');
            $table->enum('send_time_type', ['immediate', 'scheduled'])->comment('发送时间类型：immediate立即发送/scheduled定时发送');
            $table->timestamp('scheduled_time')->nullable()->comment('定时发送时间');
            $table->boolean('is_draft')->default(false)->comment('是否为草稿：true是/false否');
            $table->enum('status', ['draft', 'pending', 'sent', 'failed'])->default('draft')->comment('状态：draft草稿/pending待发送/sent已发送/failed发送失败');
            $table->unsignedBigInteger('created_id')->nullable()->comment('创建人ID');
            $table->timestamp('sent_at')->nullable()->comment('实际发送时间');
            $table->timestamps();

            $table->foreign('created_id')
                ->references('id')
                ->on('lab_users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_notifications');
    }
};
