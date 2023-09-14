<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('event_id');
            $table->unsignedBigInteger('event_owner_id')->nullable(true);
            $table->string('event_name', 100)->nullable(true);
            $table->string('event_image', 40)->nullable(true);
            $table->string('event_location', 250)->nullable(true);
            $table->string('event_latitude', 30)->nullable(true);
            $table->string('event_longitude', 30)->nullable(true);
            $table->text('event_description')->nullable(true);
            $table->date('event_start_date')->nullable(true);
            $table->time('event_start_time')->nullable(true);
            $table->date('event_end_date')->nullable(true);
            $table->time('event_end_time')->nullable(true);
            $table->enum('event_participants', ['Public', 'Friends & Groups'])->nullable(true);
            $table->unsignedTinyInteger("event_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('event_created_at')->nullable(true);
            $table->timestamp('event_updated_at')->nullable(true);
            $table->timestamp('event_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('events');
    }

}
