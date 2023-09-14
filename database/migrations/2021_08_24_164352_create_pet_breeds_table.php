<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetBreedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pet_breeds', function (Blueprint $table) {
            $table->bigIncrements('pb_id');
            $table->string('pb_name', 100)->nullable(true);
            $table->unsignedTinyInteger("pb_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('pb_created_at')->nullable(true);
            $table->timestamp('pb_updated_at')->nullable(true);
            $table->timestamp('pb_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pet_breeds');
    }
}
