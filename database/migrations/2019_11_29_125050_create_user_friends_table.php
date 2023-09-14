<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFriendsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_friends', function (Blueprint $table) {
            $table->bigIncrements('ufr_id');
            $table->unsignedBigInteger('ufr_user_id')->nullable(true);
            $table->unsignedBigInteger('ufr_invited_user_id')->nullable(true);
            $table->string('ufr_email', 30)->nullable(true);
            $table->string('ufr_token')->nullable(true);
            $table->unsignedTinyInteger("ufr_status")->comment('1-Accept 2-Pending')->default(1);
            $table->timestamp('ufr_created_at')->nullable(true);
            $table->timestamp('ufr_updated_at')->nullable(true);
            $table->timestamp('ufr_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_friends');
    }

}
