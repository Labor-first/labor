<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('questions')) {
            return;
        }
        
        Schema::create('questions', function (Blueprint $table) {
            $table->id()->comment('问题ID，主键');
            $table->unsignedBigInteger('user_id')->comment('提问学员ID');
            $table->string('title', 255)->comment('问题标题');
            $table->text('content')->comment('问题内容');
            $table->enum('status', ['pending', 'answered', 'resolved'])->default('pending')->comment('状态：待回复、已回复、已解决');
            $table->text('answer')->nullable()->comment('回复内容');
            $table->unsignedBigInteger('answered_by')->nullable()->comment('回复人ID（管理员）');
            $table->timestamp('answered_at')->nullable()->comment('回复时间');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};