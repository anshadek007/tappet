<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->bigIncrements('al_id');
            $table->unsignedTinyInteger('al_response_code')->nullable(true);
            $table->string('al_api_name', 100)->nullable(true);
            $table->string('al_api_method', 100)->nullable(true);
            $table->string("al_ip_address", 100)->nullable(true);
            $table->text('al_request_data')->nullable(true);
            $table->text('al_response_data')->nullable(true);
            $table->float('al_processing_time',30,10)->nullable(true)->comment('in seconds');
            $table->string("al_device_type", 20)->nullable(true);
            $table->unsignedTinyInteger('al_authorized')->nullable(true);
            $table->timestamp('al_created_at')->nullable(true);
            $table->timestamp('al_updated_at')->nullable(true);
            $table->timestamp('al_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_logs');
    }
}
