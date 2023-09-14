<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserCorporateDetails extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->string("u_corporation_name", 200)->nullable(true)->after('u_user_type');
            $table->string("u_website", 200)->nullable(true)->after('u_corporation_name');
            $table->string("u_alternate_phone_number", 100)->nullable(true)->after('u_website');
            $table->enum("u_timing",['Selected Days','24 Hours'])->nullable(true)->after('u_alternate_phone_number');
            $table->string("u_start_time", 100)->nullable(true)->after('u_timing');
            $table->string("u_end_time", 100)->nullable(true)->after('u_start_time');
            $table->string("u_week_days", 100)->nullable(true)->after('u_end_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('users', function (Blueprint $table) {
            // 
        });
    }

}
