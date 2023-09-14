<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetCollarsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pet_collars', function (Blueprint $table) {
            $table->bigIncrements('pet_collar_id');
            $table->string('pet_collar_device_id')->nullable(true);
            $table->unsignedBigInteger('pet_collar_pet_id')->nullable(true);
            $table->unsignedTinyInteger("pet_collar_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('pet_collar_created_at')->nullable(true);
            $table->timestamp('pet_collar_updated_at')->nullable(true);
            $table->timestamp('pet_collar_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pet_collars');
    }

}
