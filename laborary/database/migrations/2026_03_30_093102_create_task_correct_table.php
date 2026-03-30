<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_correct', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->comment('任务ID');
            $table->string('correct_status')->default('uncorrected')->comment('批改状态');
            $table->integer('score')->nullable()->comment('分数');
            $table->text('comment')->nullable()->comment('评语');
            $table->unsignedBigInteger('corrector_id')->nullable()->comment('批改人ID');
            $table->timestamps();
            
            $table->foreign('task_id')->references('id')->on('task')->onDelete('cascade');
            $table->foreign('corrector_id')->references('id')->on('lab_users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_correct');
    }
};