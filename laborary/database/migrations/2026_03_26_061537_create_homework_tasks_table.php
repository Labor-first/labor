<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 作业任务表
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('homework_tasks', function (Blueprint $table) {
        $table->id()->comment('作业任务ID');
        $table->string('title')->comment('作业标题');
        $table->text('content')->comment('作业内容/要求');
        $table->string('attachment')->nullable()->comment('附件');
        $table->timestamp('deadline')->nullable()->comment('截止时间');
        $table->unsignedBigInteger('created_by')->comment('发布人ID');
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homeworks');
    }
};