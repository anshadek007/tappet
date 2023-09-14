<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->bigIncrements('pet_id');
            $table->unsignedBigInteger('pet_type_id')->nullable(true);
            $table->unsignedBigInteger('pet_owner_id')->nullable(true);
            $table->string('pet_name', 100)->nullable(true);
            $table->enum('pet_gender', ['Male', 'Female'])->default('Male');
            $table->date('pet_dob')->nullable(true);
            $table->string('pet_age', 40)->nullable(true);
            $table->string('pet_image', 40)->nullable(true);
            $table->text('pet_breed_ids')->nullable(true);
            $table->text('pet_breed_percentage')->nullable(true);
            $table->unsignedTinyInteger("pet_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('pet_created_at')->nullable(true);
            $table->timestamp('pet_updated_at')->nullable(true);
            $table->timestamp('pet_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pets');
    }
}
