<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTermsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('terms_and_conditions', function (Blueprint $table) {
            $table->bigIncrements('tc_id');
            $table->string('tc_title', 255)->nullable(true);
            $table->text('tc_description')->nullable(true);
            $table->unsignedTinyInteger("tc_status")->comment('1-Active 2-Inactive')->default(1);
            $table->timestamp('tc_created_at')->nullable(true);
            $table->timestamp('tc_updated_at')->nullable(true);
            $table->timestamp('tc_deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('terms_and_conditions');
    }

}
