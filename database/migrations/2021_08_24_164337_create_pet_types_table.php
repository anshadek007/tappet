<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pet_types', function (Blueprint $table) {
            $table->bigIncrements('pt_id');
            $table->string('pt_name', 100)->nullable(true);
            $table->string('pt_image', 50)->nullable(true);
            $table->unsignedTinyInteger("pt_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('pt_created_at')->nullable(true);
            $table->timestamp('pt_updated_at')->nullable(true);
            $table->timestamp('pt_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pet_types');
    }
}
