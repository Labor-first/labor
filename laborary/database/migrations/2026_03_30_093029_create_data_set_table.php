<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_set', function (Blueprint $table) {
            $table->id();
            $table->string('data_set_name')->comment('数据集名称');
            $table->text('data_set_desc')->nullable()->comment('数据集描述');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_set');
    }
};