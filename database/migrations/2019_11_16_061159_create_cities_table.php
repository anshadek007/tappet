<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCitiesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('cities', function (Blueprint $table) {
            $table->bigIncrements('city_id');
            $table->unsignedInteger('city_country_id')->nullable(true);
            $table->string('city_name', 255)->nullable(true);
            $table->unsignedTinyInteger("city_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('city_created_at')->nullable(true);
            $table->timestamp('city_updated_at')->nullable(true);
            $table->timestamp('city_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('cities');
    }

}
