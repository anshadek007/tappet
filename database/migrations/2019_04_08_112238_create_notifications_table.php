<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('n_id');
            $table->unsignedBigInteger('n_reciever_id')->nullable(true);
            $table->unsignedBigInteger('n_sender_id')->nullable(true);
            $table->unsignedBigInteger('n_nd_id')->nullable(true);
            $table->string('n_message')->nullable(true);
            $table->text('n_params')->nullable(true);
            $table->unsignedTinyInteger("n_notification_type")->default(1);
            $table->unsignedTinyInteger("n_status")->comment('1-Read, 2-Pending for send, 3=Unread, 9=Deleted')->default(1);
            $table->timestamp('n_created_at')->nullable(true);
            $table->timestamp('n_updated_at')->nullable(true);
            $table->timestamp('n_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('notifications');
    }

}
