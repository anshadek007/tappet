<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCallHistoriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_call_histories', function (Blueprint $table) {
            $table->bigIncrements('call_history_id');
            $table->unsignedBigInteger('call_from_user_id')->nullable(true);
            $table->unsignedBigInteger('call_to_user_id')->nullable(true);
            $table->unsignedInteger('call_duration')->nullable(true)->default(0);
            $table->dateTime('call_datetime')->nullable(true);
            $table->unsignedTinyInteger("call_history_status")->comment('1-Active, 2-Inactive, 9-Deleted')->default(1);
            $table->timestamp('call_history_created_at')->nullable(true);
            $table->timestamp('call_history_updated_at')->nullable(true);
            $table->timestamp('call_history_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_call_histories');
    }

}
