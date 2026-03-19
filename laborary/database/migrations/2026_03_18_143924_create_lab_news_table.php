<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('lab_news', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('标题');
            $table->text('content')->comment('内容');
            $table->string('cover')->nullable()->comment('封面图');
            $table->tinyInteger('is_top')->default(0)->comment('置顶');
            $table->unsignedBigInteger('author_id')->comment('作者ID');
            $table->timestamp('published_at')->nullable()->comment('定时发布时间');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lab_news');
    }
};