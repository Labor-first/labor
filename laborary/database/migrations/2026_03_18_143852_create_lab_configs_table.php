<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('lab_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('实验室名称');
            $table->text('intro')->comment('实验室简介');
            $table->string('address')->comment('实验室地址');
            $table->string('contact')->comment('联系方式');
            $table->timestamps();
            $table->unique('id'); // 强制单条数据
        });
    }

    public function down()
    {
        Schema::dropIfExists('lab_configs');
    }
};
