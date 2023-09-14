<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePetAddSizeAndFriendly extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('pets', function (Blueprint $table) {
            $table->string('pet_size', 100)->nullable(true)->after('pet_age');
            $table->enum('pet_is_friendly', ['Yes', 'No'])->nullable(true)->after('pet_size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('pets', function (Blueprint $table) {
            
        });
    }

}
