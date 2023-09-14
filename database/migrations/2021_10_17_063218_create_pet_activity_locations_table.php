<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetActivityLocationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pet_activity_locations', function (Blueprint $table) {
            $table->bigIncrements('pet_activity_location_id');
            $table->unsignedBigInteger('pet_activity_location_activity_id')->nullable(true);
            $table->string('pet_activity_location_latitude')->nullable(true);
            $table->string('pet_activity_location_longitude')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pet_activity_location_locations');
    }

}
