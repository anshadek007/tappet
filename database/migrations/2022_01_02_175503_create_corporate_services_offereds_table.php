<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorporateServicesOfferedsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('corporate_services_offereds', function (Blueprint $table) {
            $table->bigIncrements('corporate_services_offered_id');
            $table->unsignedBigInteger('corporate_services_offered_owner_id')->nullable(true);
            $table->unsignedBigInteger('corporate_services_offered_service_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('corporate_services_offereds');
    }

}
