<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostImagesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('post_images', function (Blueprint $table) {
            $table->bigIncrements('post_image_id');
            $table->unsignedBigInteger('post_image_post_id')->nullable(true);
            $table->string('post_image_image', 40)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('post_images');
    }

}
