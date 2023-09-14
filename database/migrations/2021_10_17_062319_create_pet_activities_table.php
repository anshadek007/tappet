<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetActivitiesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pet_activities', function (Blueprint $table) {
            $table->bigIncrements('pet_activity_id');
            $table->unsignedBigInteger('pet_activity_pet_id')->nullable(true);
            $table->dateTime('pet_activity_start_date_time')->nullable(true);
            $table->dateTime('pet_activity_end_date_time')->nullable(true);
            $table->unsignedTinyInteger("pet_activity_status")->comment('1-Completed 2-In-Progress')->default(2);
            $table->timestamp('pet_activity_created_at')->nullable(true);
            $table->timestamp('pet_activity_updated_at')->nullable(true);
            $table->timestamp('pet_activity_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pet_activities');
    }

}
