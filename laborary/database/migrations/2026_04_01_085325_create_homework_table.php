<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('homework', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('attachment')->nullable();
            $table->dateTime('deadline');
            $table->integer('creator_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('homework');
    }
};
