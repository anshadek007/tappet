<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserBlocksTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->bigIncrements('user_block_id');
            $table->unsignedBigInteger('user_block_user_id')->nullable(true);
            $table->unsignedBigInteger('user_block_blocked_user_id')->nullable(true);
            $table->unsignedTinyInteger("user_block_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('user_block_created_at')->nullable(true);
            $table->timestamp('user_block_updated_at')->nullable(true);
            $table->timestamp('user_block_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_blocks');
    }

}
