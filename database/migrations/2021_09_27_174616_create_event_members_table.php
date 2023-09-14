<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventMembersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('event_members', function (Blueprint $table) {
            $table->bigIncrements('em_id');
            $table->unsignedBigInteger('em_user_id')->nullable(true);
            $table->unsignedBigInteger('em_event_id')->nullable(true);
            $table->unsignedTinyInteger("em_status")->comment('1-Goindg 2-Not Going 3-Interested')->default(2);
            $table->timestamp('em_created_at')->nullable(true);
            $table->timestamp('em_updated_at')->nullable(true);
            $table->timestamp('em_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('event_members');
    }

}
