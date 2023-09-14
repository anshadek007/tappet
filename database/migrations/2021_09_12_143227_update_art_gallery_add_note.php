<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateArtGalleryAddNote extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('pets', function (Blueprint $table) {
            $table->text('pet_note')->nullable(true)->after('pet_age');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('pets', function (Blueprint $table) {
            //
        });
    }

}
