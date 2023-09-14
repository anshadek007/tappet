<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostCommentsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->bigIncrements('post_comment_id');
            $table->unsignedBigInteger('post_comment_user_id')->nullable(true);
            $table->unsignedBigInteger('post_comment_post_id')->nullable(true);
            $table->string('post_comment_text', 250)->nullable(true);
            $table->unsignedTinyInteger("post_comment_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('post_comment_created_at')->nullable(true);
            $table->timestamp('post_comment_updated_at')->nullable(true);
            $table->timestamp('post_comment_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('post_comments');
    }

}
