<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorporateFollwersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporate_follwers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('u_id')->nullable();
            $table->unsignedBigInteger('cor_u_id')->nullable();
            $table->integer('follow')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('corporate_follwers');
    }
}
