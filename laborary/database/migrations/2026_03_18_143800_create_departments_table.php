<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('部门名称');
            $table->text('intro')->nullable()->comment('部门介绍');
            $table->string('tech_stack')->nullable()->comment('技术栈');
            $table->integer('sort')->default(0);
            //关联实验室（固定为1，因为只有一个实验室）
            $table->integer('lab_id')->default(1)->comment('所属实验室ID');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
};