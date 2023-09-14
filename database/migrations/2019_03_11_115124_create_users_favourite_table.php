<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersFavouriteTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users_favourite', function (Blueprint $table) {
            $table->bigIncrements('uf_id');
            $table->unsignedBigInteger('uf_tour_id')->nullable(true);
            $table->unsignedBigInteger('uf_u_id')->nullable(true);
            $table->unsignedTinyInteger("uf_status")->comment('1-Like 2-Unlike')->default(1);
            $table->timestamp('uf_created_at')->nullable(true);
            $table->timestamp('uf_updated_at')->nullable(true);
            $table->timestamp('uf_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users_favourite');
    }

}
