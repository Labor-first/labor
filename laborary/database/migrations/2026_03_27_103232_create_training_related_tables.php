<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
     public function up()
    {
        // 1. "培训周次表" (training_week)
        if (!Schema::hasTable('training_week')) {
            Schema::create('training_week', function (Blueprint $table) {
                $table->id();
                $table->string('week_name', 50)->comment('周次名称，如 第1周、Week 1');
                $table->date('start_date')->comment('开始日期');
                $table->date('end_date')->comment('结束日期');
                $table->text('description')->nullable()->comment('备注/描述');
                $table->boolean('is_published')->default(false)->comment('是否发布');
                $table->timestamp('published_at')->nullable()->comment('发布时间');
                $table->timestamps();
            });
        }

        // 2. "培训学员表" (training_student)
        if (!Schema::hasTable('training_student')) {
            Schema::create('training_student', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->comment('学员姓名');
                $table->string('email', 100)->unique()->comment('邮箱，唯一');
                $table->string('phone', 20)->nullable()->comment('电话');
                $table->foreignId('week_id')->constrained('training_week')->onDelete('cascade')->comment('关联的周次ID');
                $table->timestamps();
            });
        }

        // 3. "作业表" (homeworks)
        if (!Schema::hasTable('homeworks')) {
            Schema::create('homeworks', function (Blueprint $table) {
                $table->id();
                $table->string('title', 100)->comment('作业标题');
                $table->text('content')->comment('作业内容');
                $table->foreignId('student_id')->constrained('training_student')->onDelete('cascade')->comment('所属学员ID');
                $table->foreignId('week_id')->constrained('training_week')->onDelete('cascade')->comment('所属周次ID');
                $table->enum('status', ['pending', 'submitted', 'graded'])->default('pending')->comment('状态：待提交、已提交、已评分');
                $table->timestamps();
            });
        }
    }

 
    public function down()
    {
        Schema::dropIfExists('homeworks');
        Schema::dropIfExists('training_student');
        Schema::dropIfExists('training_week');
    }
};