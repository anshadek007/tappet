<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetCoOwnersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pet_co_owners', function (Blueprint $table) {
            $table->bigIncrements('pet_co_owner_id');
            $table->unsignedBigInteger('pet_co_owner_owner_id')->nullable(true);
            $table->unsignedBigInteger('pet_co_owner_pet_id')->nullable(true);
            $table->unsignedTinyInteger("pet_co_owner_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('pet_co_owner_created_at')->nullable(true);
            $table->timestamp('pet_co_owner_updated_at')->nullable(true);
            $table->timestamp('pet_co_owner_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pet_co_owners');
    }

}
