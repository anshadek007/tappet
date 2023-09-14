<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('post_id');
            $table->unsignedBigInteger('post_owner_id')->nullable(true);
            $table->string('post_name', 250)->nullable(true);
            $table->string('post_image', 40)->nullable(true);
            $table->string('post_location', 250)->nullable(true);
            $table->string('post_latitude', 40)->nullable(true);
            $table->string('post_longitude', 40)->nullable(true);
            $table->unsignedBigInteger('post_event_id')->nullable(true);
            $table->string('post_type')->comment('1=Photo, 2=Location, 3=Event, 4=Multiple Photos, 5=Audio, 6=Video')->nullable(true);
            $table->unsignedTinyInteger("post_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('post_created_at')->nullable(true);
            $table->timestamp('post_updated_at')->nullable(true);
            $table->timestamp('post_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('posts');
    }

}
