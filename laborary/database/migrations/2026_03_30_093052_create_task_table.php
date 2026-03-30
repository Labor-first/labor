<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->string('task_name')->comment('任务名称');
            $table->unsignedBigInteger('model_id')->comment('模型ID');
            $table->json('train_params')->comment('训练参数');
            $table->unsignedBigInteger('data_set_id')->comment('数据集ID');
            $table->text('submit_desc')->nullable()->comment('提交说明');
            $table->timestamp('start_time')->comment('开始时间');
            $table->timestamp('end_time')->comment('结束时间');
            $table->string('task_status')->comment('任务状态');
            $table->integer('progress')->default(0)->comment('进度');
            $table->string('compute_resource')->nullable()->comment('计算资源');
            $table->timestamps();
            
            $table->foreign('model_id')->references('id')->on('model')->onDelete('cascade');
            $table->foreign('data_set_id')->references('id')->on('data_set')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task');
    }
};