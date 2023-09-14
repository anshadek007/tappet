<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventImagesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('event_images', function (Blueprint $table) {
            $table->bigIncrements('event_image_id');
            $table->unsignedBigInteger('event_image_event_id')->nullable(true);
            $table->unsignedBigInteger('event_image_user_id')->nullable(true);
            $table->string('event_image_image', 40)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('event_images');
    }

}
