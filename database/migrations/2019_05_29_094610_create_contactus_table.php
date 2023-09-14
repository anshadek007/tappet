<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contactus', function (Blueprint $table) {
            $table->bigIncrements('con_id');
            $table->string('con_name',80)->nullable(true);
            $table->string('con_title',255)->nullable(true);
            $table->string('con_email', 150)->nullable(true);
            $table->string('con_mobile_number', 20)->nullable(true);
            $table->text('con_msg')->nullable(true);
            $table->unsignedTinyInteger("con_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('con_created_at')->nullable(true);
            $table->timestamp('con_updated_at')->nullable(true);
            $table->timestamp('con_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contactus');
    }
}
