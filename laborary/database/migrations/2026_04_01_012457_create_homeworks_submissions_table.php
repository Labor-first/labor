<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 作业提交表
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('homework_submissions', function (Blueprint $table) {
          Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id()->comment('提交ID');
            $table->unsignedBigInteger('task_id')->comment('作业任务ID');
            $table->unsignedBigInteger('user_id')->comment('学员ID');
            $table->text('content')->nullable()->comment('提交内容');
            $table->string('attachment')->nullable()->comment('附件');
            $table->enum('status', ['submitted', 'corrected'])->default('submitted');// 提交状态：已提交/已批改
            $table->integer('score')->nullable()->comment('得分');
            $table->text('comment')->nullable()->comment('评语');
            $table->timestamps();
});
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
    }
};