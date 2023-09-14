<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostLikesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('post_likes', function (Blueprint $table) {
            $table->bigIncrements('post_like_id');
            $table->unsignedBigInteger('post_like_post_id')->nullable(true);
            $table->unsignedBigInteger('post_like_user_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('post_likes');
    }

}
