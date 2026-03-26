<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content')->nullable()->comment('作业内容');
            $table->string('attachment')->nullable()->comment('附件地址');
            $table->integer('score')->nullable()->comment('得分');
            $table->text('comment')->nullable()->comment('批改评语');
            $table->string('status')
                ->default('unsubmitted')
                ->comment('unsubmitted未提交 / submitted已提交 / pending_correction待批改 / corrected已批改 / rejected打回');
            $table->string('week')->nullable()->comment('周次：第1周');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('homeworks');
    }
};
