<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventGroupsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('event_groups', function (Blueprint $table) {
            $table->bigIncrements('eg_id');
            $table->unsignedBigInteger('eg_group_id')->nullable(true);
            $table->unsignedBigInteger('eg_event_id')->nullable(true);
            $table->timestamp('eg_created_at')->nullable(true);
            $table->timestamp('eg_updated_at')->nullable(true);
            $table->timestamp('eg_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('event_groups');
    }

}
