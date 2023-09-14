<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetLocationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pet_locations', function (Blueprint $table) {
            $table->bigIncrements('pet_location_id');
            $table->unsignedBigInteger('pet_location_pet_id')->nullable(true);
            $table->string('pet_location_latitude')->nullable(true);
            $table->string('pet_location_longitude')->nullable(true);
            $table->enum('pet_location_status', ['Live', 'Stop'])->nullable(true);
            $table->timestamp('pet_location_created_at')->nullable(true);
            $table->timestamp('pet_location_updated_at')->nullable(true);
            $table->timestamp('pet_location_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pet_locations');
    }

}
