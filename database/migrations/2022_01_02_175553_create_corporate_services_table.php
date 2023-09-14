<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorporateServicesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('corporate_services', function (Blueprint $table) {
            $table->bigIncrements('corporate_service_id');
            $table->string('corporate_service_name')->nullable(true);
            $table->unsignedTinyInteger("corporate_service_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('corporate_service_created_at')->nullable(true);
            $table->timestamp('corporate_service_updated_at')->nullable(true);
            $table->timestamp('corporate_service_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('corporate_services');
    }

}
