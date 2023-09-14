<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('countries', function (Blueprint $table) {
            $table->bigIncrements('c_id');
            $table->string('c_name', 255)->nullable(true);
            $table->string('c_flag', 40)->nullable(true);
            $table->unsignedTinyInteger("c_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('c_created_at')->nullable(true);
            $table->timestamp('c_updated_at')->nullable(true);
            $table->timestamp('c_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('countries');
    }

}
