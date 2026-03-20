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
        Schema::create('application_forms', function (Blueprint $table) {
            $table->id()->comment('报名记录ID，主键');
            $table->unsignedBigInteger('config_id')->comment('报名表配置ID');
            $table->unsignedBigInteger('user_id')->comment('报名人ID');
            $table->tinyInteger('status')->default(1)->comment('报名状态(1:待审核,2:报名成功,3:已取消,4:审核拒绝)');
            $table->timestamp('audit_time')->nullable()->comment('审核时间');
            $table->text('audit_remark')->nullable()->comment('审核备注/拒绝原因');
            $table->integer('class')->comment('班级');
            $table->string('academy', 20)->comment('学院');
            $table->string('major', 20)->comment('专业');
            $table->string('director_name', 20)->comment('导员姓名');
            $table->text('sign_reason')->comment('报名理由');
            $table->index('user_id');
            $table->index('status');
            $table->timestamps();
            
            $table->foreign('config_id')->references('id')->on('registration_configs')->onDelete('cascade');
            $table->unique(['config_id', 'user_id'], 'unique_user_config');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_forms');
    }
};
